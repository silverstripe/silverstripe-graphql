<?php

namespace SilverStripe\GraphQL\Tests;

use Exception;
use GraphQL\Type\Definition\Type;
use LogicException;
use PHPUnit_Framework_MockObject_MockBuilder;
use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Auth\Handler;
use SilverStripe\GraphQL\Controller;
use SilverStripe\GraphQL\Extensions\IntrospectionProvider;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Middleware\CSRFMiddleware;
use SilverStripe\GraphQL\Middleware\HTTPMethodMiddleware;
use SilverStripe\GraphQL\Tests\Fake\FakePersistedQuery;
use SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake;
use SilverStripe\Security\SecurityToken;

class ControllerTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function setUp()
    {
        parent::setUp();

        Handler::config()->remove('authenticators');
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        // Disable CORS Config by default.
        Controller::config()->set('cors', [ 'Enabled' => false ]);

        TestAssetStore::activate('GraphQLController');
    }

    public function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testIndex()
    {
        $manager = new Manager();
        $controller = new Controller($manager);

        $manager->addType($this->getType($manager), 'mytype');
        $manager->addQuery($this->getQuery($manager), 'myquery');
        $controller->setManager($manager);
        $response = $controller->index(new HTTPRequest('GET', ''));
        $this->assertFalse($response->isError());
    }

    public function testGetGetManagerPopulatesFromConfig()
    {
        Config::modify()->set(Manager::class, 'schemas', [
        'testSchema' => [
            'types' => [
                'mytype' => TypeCreatorFake::class,
            ],
        ]
        ]);
        $manager = new Manager('testSchema');
        $controller = new Controller($manager);
        $this->assertNotNull(
            $controller->getManager()->getType('mytype')
        );
    }

    public function testIndexWithException()
    {
        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        $kernel->setEnvironment(Kernel::LIVE);

        /** @var Manager|PHPUnit_Framework_MockObject_MockBuilder $managerMock */
        $managerMock = $this->getMockBuilder(Manager::class)
        ->setMethods(['query'])
        ->getMock();

        $managerMock->method('query')
        ->will($this->throwException(new Exception('Failed')));

        $controller = new Controller($managerMock);
        $response = $controller->index(new HTTPRequest('GET', ''));
        $this->assertFalse($response->isError());
        $responseObj = json_decode($response->getBody(), true);
        $this->assertNotNull($responseObj);
        $this->assertArrayHasKey('errors', $responseObj);
        $this->assertEquals('Failed', $responseObj['errors'][0]['message']);
        $this->assertArrayNotHasKey('trace', $responseObj['errors'][0]);
    }

    public function testIndexWithExceptionIncludesTraceInDevMode()
    {
        /** @var Manager|PHPUnit_Framework_MockObject_MockBuilder $managerMock */
        $managerMock = $this->getMockBuilder(Manager::class)
        ->setMethods(['query'])
        ->getMock();

        $managerMock->method('query')
        ->will($this->throwException(new Exception('Failed')));

        $controller = new Controller($managerMock);
        $response = $controller->index(new HTTPRequest('GET', ''));
        $this->assertFalse($response->isError());
        $responseObj = json_decode($response->getBody(), true);
        $this->assertNotNull($responseObj);
        $this->assertArrayHasKey('errors', $responseObj);
        $this->assertEquals('Failed', $responseObj['errors'][0]['message']);
        $this->assertArrayHasKey('trace', $responseObj['errors'][0]);
    }

    /**
     * Test that an instance of the authentication handler is returned
     */
    public function testGetAuthHandler()
    {
        $controller = new Controller();
        $this->assertInstanceOf(Handler::class, $controller->getAuthHandler());
    }

    /**
     * Test that authentication can work or not, but that a response is still given to the client
     *
     * @param string $authenticator
     * @param string $shouldFail
     * @dataProvider authenticatorProvider
     */
    public function testAuthenticationProtectionOnQueries($authenticator, $shouldFail)
    {
        Handler::config()->update('authenticators', [
        ['class' => $authenticator]
        ]);

        $controller = new Controller($manager = new Manager());
        $manager->addType($this->getType($manager), 'mytype');
        $manager->addQuery($this->getQuery($manager), 'myquery');

        $response = $controller->index(new HTTPRequest('GET', ''));

        $assertion = ($shouldFail) ? 'assertContains' : 'assertNotContains';
        // See Fake\BrutalAuthenticatorFake::authenticate for failure message
        $this->{$assertion}('Never!', $response->getBody());
    }

    /**
     * @return array[]
     */
    public function authenticatorProvider()
    {
        return [
        [
            Fake\PushoverAuthenticatorFake::class,
            false,
        ],
        [
            Fake\BrutalAuthenticatorFake::class,
            true
        ]
        ];
    }

    /**
     * @expectedException \SilverStripe\Control\HTTPResponse_Exception
     */
    public function testAddCorsHeadersOriginDisallowed()
    {
        Config::modify()->set(Controller::class, 'cors', [
        'Enabled' => true,
        'Allow-Origin' => null,
        'Allow-Headers' => 'Authorization, Content-Type',
        'Allow-Methods' =>  'GET, POST, OPTIONS',
        'Max-Age' => 86400
        ]);

        $controller = new Controller();
        $request = new HTTPRequest('GET', '');
        $request->addHeader('Origin', 'localhost');
        $response = new HTTPResponse();
        $response = $controller->addCorsHeaders($request, $response);

        $this->assertTrue($response instanceof HTTPResponse);
        $this->assertEquals($response->getStatusCode(), '403');
    }

    public function testAddCorsHeadersOriginAllowed()
    {
        Config::modify()->set(Controller::class, 'cors', [
        'Enabled' => true,
        'Allow-Origin' => 'http://localhost',
        'Allow-Headers' => 'Authorization, Content-Type',
        'Allow-Methods' =>  'GET, POST, OPTIONS',
        'Max-Age' => 86400
        ]);

        $controller = new Controller();
        $request = new HTTPRequest('GET', '');
        $request->addHeader('Origin', 'http://localhost');
        $response = new HTTPResponse();
        $response = $controller->addCorsHeaders($request, $response);

        $this->assertTrue($response instanceof HTTPResponse);
        $this->assertEquals('200', $response->getStatusCode());

        // Check returned headers.  A valid origin should return 4 headers.
        $this->assertEquals('http://localhost', $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertEquals('Authorization, Content-Type', $response->getHeader('Access-Control-Allow-Headers'));
        $this->assertEquals('GET, POST, OPTIONS', $response->getHeader('Access-Control-Allow-Methods'));
        $this->assertEquals(86400, $response->getHeader('Access-Control-Max-Age'));
    }

    public function testAddCorsHeadersRefererAllowed()
    {
        Config::modify()->set(Controller::class, 'cors', [
        'Enabled' => true,
        'Allow-Origin' => 'http://localhost',
        'Allow-Headers' => 'Authorization, Content-Type',
        'Allow-Methods' =>  'GET, POST, OPTIONS',
        'Max-Age' => 86400
        ]);

        $controller = new Controller();
        $request = new HTTPRequest('GET', '');
        $request->addHeader('Referer', 'http://localhost/some-url/?bob=1');
        $response = new HTTPResponse();
        $response = $controller->addCorsHeaders($request, $response);

        $this->assertTrue($response instanceof HTTPResponse);
        $this->assertEquals('200', $response->getStatusCode());

        // Check returned headers.  A valid origin should return 4 headers.
        $this->assertEquals('http://localhost', $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertEquals('Authorization, Content-Type', $response->getHeader('Access-Control-Allow-Headers'));
        $this->assertEquals('GET, POST, OPTIONS', $response->getHeader('Access-Control-Allow-Methods'));
        $this->assertEquals(86400, $response->getHeader('Access-Control-Max-Age'));
    }

    public function testAddCorsHeadersRefererPortAllowed()
    {
        Config::modify()->set(Controller::class, 'cors', [
        'Enabled' => true,
        'Allow-Origin' => 'http://localhost:8181',
        'Allow-Headers' => 'Authorization, Content-Type',
        'Allow-Methods' =>  'GET, POST, OPTIONS',
        'Max-Age' => 86400
        ]);

        $controller = new Controller();
        $request = new HTTPRequest('GET', '');
        $request->addHeader('Referer', 'http://localhost:8181/some-url/?bob=1');
        $response = new HTTPResponse();
        $response = $controller->addCorsHeaders($request, $response);

        $this->assertTrue($response instanceof HTTPResponse);
        $this->assertEquals('200', $response->getStatusCode());

        // Check returned headers.  A valid origin should return 4 headers.
        $this->assertEquals('http://localhost:8181', $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertEquals('Authorization, Content-Type', $response->getHeader('Access-Control-Allow-Headers'));
        $this->assertEquals('GET, POST, OPTIONS', $response->getHeader('Access-Control-Allow-Methods'));
        $this->assertEquals(86400, $response->getHeader('Access-Control-Max-Age'));
    }

    /**
     * Test fail on referer port
     */
    public function testAddCorsHeadersRefererPortDisallowed()
    {
        $this->expectException(HTTPResponse_Exception::class);

        Config::modify()->set(Controller::class, 'cors', [
        'Enabled' => true,
        'Allow-Origin' => 'http://localhost:9090',
        'Allow-Headers' => 'Authorization, Content-Type',
        'Allow-Methods' =>  'GET, POST, OPTIONS',
        'Max-Age' => 86400
        ]);

        $controller = new Controller();
        $request = new HTTPRequest('GET', '');
        $request->addHeader('Referer', 'http://localhost:8080/some-url/?bob=1');
        $response = new HTTPResponse();
        $controller->addCorsHeaders($request, $response);
    }

    public function testAddCorsHeadersOriginAllowedWildcard()
    {
        Controller::config()->set('cors', [
        'Enabled' => true,
        'Allow-Origin' => '*',
        'Allow-Headers' => 'Authorization, Content-Type',
        'Allow-Methods' =>  'GET, PUT, OPTIONS',
        'Max-Age' => 600
        ]);

        $controller = new Controller();
        $request = new HTTPRequest('GET', '');
        $request->addHeader('Origin', 'localhost');
        $response = new HTTPResponse();
        $response = $controller->addCorsHeaders($request, $response);

        $this->assertTrue($response instanceof HTTPResponse);
        $this->assertEquals('200', $response->getStatusCode());
        $this->assertEquals('localhost', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testAddCorsHeadersOriginMissing()
    {
        $this->expectException(HTTPResponse_Exception::class);

        Controller::config()->set('cors', [
        'Enabled' => true,
        'Allow-Origin' => 'localhost',
        'Allow-Headers' => 'Authorization, Content-Type',
        'Allow-Methods' =>  'GET, POST, OPTIONS',
        'Max-Age' => 86400
        ]);

        $controller = new Controller();
        $request = new HTTPRequest('GET', '');
        $response = new HTTPResponse();
        $controller->addCorsHeaders($request, $response);
    }

    /**
     * HTTP OPTIONS without cors should error
     */
    public function testAddCorsHeadersResponseCORSDisabled()
    {
        $this->expectException(HTTPResponse_Exception::class);

        Config::modify()->set(Controller::class, 'cors', [
        'Enabled' => false
        ]);

        $controller = new Controller();
        $request = new HTTPRequest('OPTIONS', '');
        $request->addHeader('Origin', 'localhost');
        $controller->index($request);
    }

    public function testTypeCaching()
    {
        $expectedSchemaPath = TestAssetStore::base_path() . '/testSchema.types.graphql';
        $this->assertFileNotExists($expectedSchemaPath, 'Schema is not automatically cached');

        Config::modify()->set(Controller::class, 'cache_types_in_filesystem', true);
        $controller = Controller::create(new Manager('testSchema'));
        StaticSchema::setInstance($this->getStaticSchemaMock());

        $controller->processTypeCaching();

        // Static cache should now exist
        $this->assertFileExists($expectedSchemaPath, 'Schema is cached');
        $this->assertEquals('{"uncle":"cheese"}', file_get_contents($expectedSchemaPath));

        Config::modify()->set(Controller::class, 'cache_types_in_filesystem', false);
        Controller::create(new Manager('testSchema'))->processTypeCaching();

        // Static cache should be removed when caching is disabled
        $this->assertFileNotExists($expectedSchemaPath, 'Schema is not cached');
    }

    public function testIntrospectionProvider()
    {
        StaticSchema::setInstance($this->getStaticSchemaMock());

        Controller::add_extension(IntrospectionProvider::class);

        /* @var Controller|IntrospectionProvider $controller */
        $controller = new Controller(new Manager());
        $response = $controller->types(new HTTPRequest('GET', '/'));
        $this->assertEquals('{"uncle":"cheese"}', $response->getBody());
    }

    public function testSchemaIsResetPerController()
    {
        $config = [
        'schema1' => [
            'typeNames' => [DataObjectFake::class => 'testone'],
        ],
        'schema2' => [
            'typeNames' => [DataObjectFake::class => 'testtwo'],
        ]
        ];
        Config::modify()->set(Manager::class, 'schemas', $config);
        $controller1 = new Controller(new Manager('schema1'));
        $this->assertEquals('testone', StaticSchema::inst()->typeNameForDataObject(DataObjectFake::class));
        $controller2 = new Controller(new Manager('schema2'));
        $this->assertEquals('testtwo', StaticSchema::inst()->typeNameForDataObject(DataObjectFake::class));
    }

    public function testCSRFProtectionBlocksMutations()
    {
        $manager = $this->getFakeManager();
        $manager->addMiddleware(new CSRFMiddleware());
        $request = $this->createGraphqlRequest('mutation { testMutation }', 'POST');
        $controller = $this->getFakeController($request, $manager);
        $this->assertQueryError($controller, $request, '/CSRF token/');
    }

    public function testCSRFProtectionDisabled()
    {
        $manager = $this->getFakeManager();
        $request = $this->createGraphqlRequest('mutation { testMutation }', 'POST');
        $controller = $this->getFakeController($request, $manager);
        $this->assertQuerySuccess($controller, $request, 'testMutation');
    }

    public function testCSRFToken()
    {
        $manager = $this->getFakeManager();
        $manager->addMiddleware(new CSRFMiddleware());
        $request = $this->createGraphqlRequest('mutation { testMutation }', 'POST');
        $request->addHeader('X-CSRF-TOKEN', SecurityToken::inst()->getValue());
        $controller = $this->getFakeController($request, $manager, new Session([
            'SecurityID' => SecurityToken::inst()->getValue(),
        ]));
        $this->assertQuerySuccess($controller, $request, 'testMutation');
    }

    public function testQueriesDontNeedCSRF()
    {
        $manager = $this->getFakeManager();
        $manager->addMiddleware(new CSRFMiddleware());
        $request = $this->createGraphqlRequest('query { testQuery }', 'POST');
        $controller = $this->getFakeController($request, $manager);
        $this->assertQuerySuccess($controller, $request, 'testQuery');
    }

    public function testStrictHTTPMethodsGETMutationThrowsError()
    {
        $manager = $this->getFakeManager();
        $manager->addMiddleware(new HTTPMethodMiddleware());
        $request = $this->createGraphqlRequest('mutation { testMutation }', 'GET');
        $controller = $this->getFakeController($request, $manager);
        $this->assertQueryError($controller, $request, '/must use the POST/');
    }

    public function testStrictHTTPMethodsDisabled()
    {
        $manager = $this->getFakeManager();
        $request = $this->createGraphqlRequest('mutation { testMutation }', 'GET');
        $controller = $this->getFakeController($request, $manager);
        $this->assertQuerySuccess($controller, $request, 'testMutation');
    }

    public function testStrictHTTPMethodsPOSTMutationIsAccepted()
    {
        $manager = $this->getFakeManager();
        $manager->addMiddleware(new HTTPMethodMiddleware());
        $request = $this->createGraphqlRequest('mutation { testMutation }', 'POST');
        $controller = $this->getFakeController($request, $manager);
        $this->assertQuerySuccess($controller, $request, 'testMutation');
    }

    public function testStrictHTTPMethodsQueryCanBePOSTOrGET()
    {
        $manager = $this->getFakeManager();
        $manager->addMiddleware(new HTTPMethodMiddleware());
        $request = $this->createGraphqlRequest('query { testQuery }', 'POST');
        $controller = $this->getFakeController($request, $manager);
        $this->assertQuerySuccess($controller, $request, 'testQuery');
        $manager = $this->getFakeManager();
        $request = $this->createGraphqlRequest('query { testQuery }', 'GET');
        $controller = $this->getFakeController($request, $manager);
        $this->assertQuerySuccess($controller, $request, 'testQuery');
    }

    protected function getFakeManager()
    {
        $operation = [
            'args' => [],
            'type' => Type::string(),
            'resolve' => function () {
                return 'success';
            },
        ];
        $manager = new Manager();
        $manager->addMutation($operation, 'testMutation');
        $manager->addQuery($operation, 'testQuery');
        return $manager;
    }

    protected function getFakeController(HTTPRequest $request, Manager $manager, $session = null)
    {
        if (!$session) {
            $session = new Session([]);
        }
        $controller = new Controller();
        $controller->setManager($manager);
        $request->setSession($session);
        $controller->setRequest($request);
        $controller->pushCurrent();
        return $controller;
    }

    protected function createGraphqlRequest($graphql, $method = 'POST')
    {
        $postVars = $method === 'POST' ? ['query' => $graphql] : [];
        $getVars = $method === 'GET' ? ['query' => $graphql] : [];
        return new HTTPRequest($method, '/', $getVars, $postVars);
    }

    protected function assertQueryError(Controller $controller, HTTPRequest $request, $regExp)
    {
        $data = json_decode($controller->handleRequest($request)->getBody(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertCount(1, $data['errors']);
        $this->assertRegExp($regExp, $data['errors'][0]['message']);
    }

    protected function assertQuerySuccess(Controller $controller, HTTPRequest $request, $operation)
    {
        $data = json_decode($controller->handleRequest($request)->getBody(), true);
        $this->assertArrayNotHasKey('errors', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey($operation, $data['data']);
        $this->assertEquals('success', $data['data'][$operation]);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetQueryWithNoID()
    {
        /* @var $controller Controller */
        $controller = Controller::create();
        $manager = $this->getMockBuilder(Manager::class)
            ->setMethods(['getQueryFromPersistedID'])
            ->getMock();
        $manager->expects($this->never())
            ->method('getQueryFromPersistedID');

        $controller->setManager($manager);
        $expectedQuery = 'query($memberID:ID!){readOneMember(ID:$memberID){ID Email}}';
        $expectedVariables = ['memberID' => '1'];

        // normal query request: query + variables
        $request = new HTTPRequest('POST', '', [], [
            'query' => $expectedQuery,
            'variables' => json_encode($expectedVariables)
        ]);
        $controller->index($request);
    }

    public function testGetQueryWithID()
    {
        /* @var $controller Controller */
        $controller = Controller::create();
        $manager = $this->getMockBuilder(Manager::class)
            ->setMethods(['getQueryFromPersistedID'])
            ->getMock();
        $manager->expects($this->once())
            ->method('getQueryFromPersistedID')
            ->with($this->equalTo('1cd63b53-472c-4844-9017-4e81b18b386d'));

        $controller->setManager($manager);
        $expectedVariables = ['memberID' => '1'];

        // normal query request: query + variables
        $request = new HTTPRequest('POST', '', [], [
            'id' => '1cd63b53-472c-4844-9017-4e81b18b386d',
            'variables' => json_encode($expectedVariables)
        ]);
        $controller->index($request);
    }

    public function testGetQueryWithQueryAndID()
    {
        /* @var $controller Controller */
        $controller = Controller::create();
        $manager = $this->getMockBuilder(Manager::class)
            ->setMethods(['getQueryFromPersistedID'])
            ->getMock();
        $manager->expects($this->never())
            ->method('getQueryFromPersistedID');

        $controller->setManager($manager);
        $expectedQuery = 'query($memberID:ID!){readOneMember(ID:$memberID){ID Email}}';
        $expectedVariables = ['memberID' => '1'];

        // normal query request: query + variables
        $request = new HTTPRequest('POST', '', [], [
            'query' => $expectedQuery,
            'id' => '1cd63b53-472c-4844-9017-4e81b18b386d',
            'variables' => json_encode($expectedVariables)
        ]);
        $result = $controller->index($request)->getBody();
        $this->assertArrayHasKey('errors', json_decode($result, true));
    }

    protected function getType(Manager $manager)
    {
        return (new TypeCreatorFake($manager))->toType();
    }

    protected function getQuery(Manager $manager)
    {
        return (new QueryCreatorFake($manager))->toArray();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStaticSchemaMock()
    {
        $mock = $this->getMockBuilder(StaticSchema::class)
            ->setMethods(['introspectTypes'])
            ->getMock();
        $mock->expects($this->any())
            ->method('introspectTypes')
            ->willReturn(['uncle' => 'cheese']);

        return $mock;
    }
}
