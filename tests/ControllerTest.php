<?php

namespace SilverStripe\GraphQL\Tests;

use Exception;
use GraphQL\Error\Error as GraphQLError;
use GraphQL\Type\Definition\Type;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\CustomValidationRule;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use GraphQL\Validator\ValidationContext;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockBuilder;
use ReflectionProperty;
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
use SilverStripe\GraphQL\Middleware\CSRFMiddleware;
use SilverStripe\GraphQL\Middleware\HTTPMethodMiddleware;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\HierarchicalObject;
use SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake;
use SilverStripe\Security\SecurityToken;

class ControllerTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        HierarchicalObject::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Handler::config()->remove('authenticators');
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        // Disable CORS Config by default.
        Controller::config()->set('cors', [ 'Enabled' => false ]);

        TestAssetStore::activate('GraphQLController');
    }

    protected function tearDown(): void
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

        /** @var Manager|MockBuilder $managerMock */
        $managerMock = $this->getMockBuilder(Manager::class)
        ->setMethods(['query'])
        ->getMock();

        $managerMock->method('query')
        ->will($this->throwException(new Exception('Failed')));

        $controller = new Controller($managerMock);
        $response = $controller->index(new HTTPRequest('GET', ''));
        $this->assertFalse($response->isError());
        $responseObj = json_decode($response->getBody() ?? '', true);
        $this->assertNotNull($responseObj);
        $this->assertArrayHasKey('errors', $responseObj);
        $this->assertEquals('Failed', $responseObj['errors'][0]['message']);
        $this->assertArrayNotHasKey('trace', $responseObj['errors'][0]);
    }

    public function testIndexWithExceptionIncludesTraceInDevMode()
    {
        /** @var Manager|MockBuilder $managerMock */
        $managerMock = $this->getMockBuilder(Manager::class)
        ->setMethods(['query'])
        ->getMock();

        $managerMock->method('query')
        ->will($this->throwException(new Exception('Failed')));

        $controller = new Controller($managerMock);
        $response = $controller->index(new HTTPRequest('GET', ''));
        $this->assertFalse($response->isError());
        $responseObj = json_decode($response->getBody() ?? '', true);
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
        // See Fake\BrutalAuthenticatorFake::authenticate for failure message
        if ($shouldFail) {
            Assert::assertStringContainsString('Never!', $response->getBody());
        } else {
            Assert::assertStringNotContainsString('Never!', $response->getBody());
        }
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

    public function testAddCorsHeadersOriginDisallowed()
    {
        $this->expectException(HTTPResponse_Exception::class);
        Config::modify()->set(Controller::class, 'cors', [
        'Enabled' => true,
        'Allow-Origin' => null,
        'Allow-Headers' => 'Authorization, Content-Type',
        'Allow-Methods' =>  'GET, POST, OPTIONS',
        'Allow-Credentials' => '',
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
        'Allow-Credentials' => '',
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
        'Allow-Credentials' => '',
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
        'Allow-Credentials' => '',
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
        'Allow-Credentials' => '',
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
        'Allow-Credentials' => '',
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
        'Allow-Credentials' => '',
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

    public function testCorsOverride()
    {
        Controller::config()->set('cors', [
            'Enabled' => true,
            'Allow-Origin' => '*',
            'Allow-Headers' => 'Authorization, Content-Type',
            'Allow-Methods' =>  'GET, PUT, OPTIONS',
            'Allow-Credentials' => '',
            'Max-Age' => 600
        ]);

        $controller = new Controller();
        $this->assertTrue($controller->getMergedCorsConfig()['Enabled']);
        $this->assertEquals('*', $controller->getMergedCorsConfig()['Allow-Origin']);
        $controller->setCorsConfig([
            'Enabled' => false,
            'Allow-Origin' => 'silverstripe.com',
        ]);
        $this->assertFalse($controller->getMergedCorsConfig()['Enabled']);
        $this->assertEquals('silverstripe.com', $controller->getMergedCorsConfig()['Allow-Origin']);

        $request = new HTTPRequest('GET', '');
        $request->addHeader('Origin', 'localhost');
        $response = new HTTPResponse();
        $response = $controller->addCorsHeaders($request, $response);

        $this->assertTrue($response instanceof HTTPResponse);
        $this->assertEquals('200', $response->getStatusCode());
        $this->assertNull($response->getHeader('Access-Control-Allow-Origin'));

        $this->expectException(HTTPResponse_Exception::class);
        $controller->setCorsConfig(['Enabled' => true]);
        $controller->addCorsHeaders($request, $response);
    }

    public function testTypeCaching()
    {
        $expectedSchemaPath = TestAssetStore::base_path() . '/testSchema.types.graphql';
        $this->assertFileDoesNotExist($expectedSchemaPath, 'Schema is not automatically cached');

        Config::modify()->set(Controller::class, 'cache_types_in_filesystem', true);
        $controller = Controller::create(new Manager('testSchema'));
        StaticSchema::setInstance($this->getStaticSchemaMock());

        $controller->processTypeCaching();

        // Static cache should now exist
        $this->assertFileExists($expectedSchemaPath, 'Schema is cached');
        $this->assertEquals('{"uncle":"cheese"}', file_get_contents($expectedSchemaPath ?? ''));

        Config::modify()->set(Controller::class, 'cache_types_in_filesystem', false);
        Controller::create(new Manager('testSchema'))->processTypeCaching();

        // Static cache should be removed when caching is disabled
        $this->assertFileDoesNotExist($expectedSchemaPath, 'Schema is not cached');
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
        Config::nest();
        Config::modify()->set(Manager::class, 'schemas', $config);
        $controller1 = new Controller(new Manager('schema1'));
        $this->assertEquals('testone', StaticSchema::inst()->typeNameForDataObject(DataObjectFake::class));
        $controller2 = new Controller(new Manager('schema2'));
        $this->assertEquals('testtwo', StaticSchema::inst()->typeNameForDataObject(DataObjectFake::class));
        Config::unnest();
        StaticSchema::reset();
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
        $controller->setRequest($request);
        $controller->setManager($manager);
        $request->setSession($session);
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
        $data = json_decode($controller->handleRequest($request)->getBody() ?? '', true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertCount(1, $data['errors']);
        $this->assertMatchesRegularExpression($regExp, $data['errors'][0]['message']);
    }

    protected function assertQuerySuccess(Controller $controller, HTTPRequest $request, $operation = null)
    {
        $controller->setRequest($request);
        $data = json_decode($controller->handleRequest($request)->getBody() ?? '', true);
        $errorMessages = [];
        foreach ($data['errors'] ?? [] as $error) {
            $errorMessages[] = '"' . $error['message'] . '"';
        }
        $this->assertArrayNotHasKey('errors', $data, 'Errors were: ' . implode(', ', $errorMessages));
        $this->assertArrayHasKey('data', $data);
        if ($operation !== null) {
            $this->assertArrayHasKey($operation, $data['data']);
            $this->assertEquals('success', $data['data'][$operation]);
        }
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
        $this->assertArrayHasKey('errors', json_decode($result ?? '', true));
    }

    /**
     * @dataProvider provideDefaultDepthLimit
     */
    public function testDefaultDepthLimit(int $queryDepth, int $limit)
    {
        // This global rule should be ignored.
        DocumentValidator::addRule(new QueryDepth(1));

        try {
            $schema = $this->createRecursiveSchema();
            $this->runDepthLimitTest($queryDepth, $limit, $schema);
        } finally {
            $this->removeDocumentValidatorRule(QueryDepth::class);
        }
    }

    public function provideDefaultDepthLimit()
    {
        return $this->createProviderForComplexityOrDepth(15);
    }

    /**
     * @dataProvider provideCustomDepthLimit
     */
    public function testCustomDepthLimit(int $queryDepth, int $limit)
    {
        // This global rule should be ignored.
        DocumentValidator::addRule(new QueryDepth(1));

        try {
            $schema = $this->createRecursiveSchema();
            $schema['test_schema']['max_query_depth'] = $limit;
            $this->runDepthLimitTest($queryDepth, $limit, $schema);
        } finally {
            $this->removeDocumentValidatorRule(QueryDepth::class);
        }
    }

    public function provideCustomDepthLimit()
    {
        return $this->createProviderForComplexityOrDepth(25);
    }

    /**
     * @dataProvider provideCustomComplexityLimit
     */
    public function testCustomComplexityLimit(int $queryComplexity, int $limit)
    {
        // This global rule should be ignored.
        DocumentValidator::addRule(new QueryComplexity(1));

        try {
            $schema = $this->createRecursiveSchema();
            $schema['test_schema']['max_query_complexity'] = $limit;
            $this->runComplexityLimitTest($queryComplexity, $limit, $schema);
        } finally {
            $this->removeDocumentValidatorRule(QueryComplexity::class);
        }
    }

    public function provideCustomComplexityLimit()
    {
        return $this->createProviderForComplexityOrDepth(10);
    }

    /**
     * @dataProvider provideDefaultNodeLimit
     */
    public function testDefaultNodeLimit(int $numNodes, int $limit)
    {
        $schema = $this->createRecursiveSchema();
        $this->runNodeLimitTest($numNodes, $limit, $schema);
    }

    public function provideDefaultNodeLimit()
    {
        return $this->createProviderForComplexityOrDepth(500);
    }

    /**
     * @dataProvider provideCustomNodeLimit
     */
    public function testCustomNodeLimit(int $numNodes, int $limit)
    {
        $schema = $this->createRecursiveSchema();
        $schema['test_schema']['max_query_nodes'] = $limit;
        $this->runNodeLimitTest($numNodes, $limit, $schema);
    }

    public function provideCustomNodeLimit()
    {
        return $this->createProviderForComplexityOrDepth(200);
    }

    public function testGlobalRuleNotRemoved()
    {
        // This global rule should NOT be ignored.
        DocumentValidator::addRule(new CustomValidationRule('never-passes', function (ValidationContext $context) {
            $context->reportError(new GraphQLError('This is the custom rule'));
            return [];
        }));

        try {
            $schema = $this->createRecursiveSchema();
            Config::modify()->set(Manager::class, 'schemas', $schema);
            $manager = new Manager('test_schema');
            $manager->configure();
            $request = $this->createGraphqlRequest($this->craftRecursiveQuery(15));
            $controller = $this->getFakeController($request, $manager);

            $this->assertQueryError($controller, $request, '/^This is the custom rule$/');
        } finally {
            $this->removeDocumentValidatorRule('never-passes');
        }
    }

    private function removeDocumentValidatorRule(string $ruleName): void
    {
        $reflectionRules = new ReflectionProperty(DocumentValidator::class, 'rules');
        $reflectionRules->setAccessible(true);
        $rules = $reflectionRules->getValue();
        unset($rules[$ruleName]);
        $reflectionRules->setValue($rules);
    }

    private function createRecursiveSchema(): array
    {
        return [
            'test_schema' => [
                'scaffolding' => [
                    'types' => [
                        HierarchicalObject::class => [
                            'fields' => '*',
                            'operations' => '*',
                            'nestedQueries' => [
                                'Children' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function createProviderForComplexityOrDepth(int $limit): array
    {
        return [
            'far less than limit' => [1, $limit],
            'one less than limit' => [$limit - 1, $limit],
            'exactly at the limit' => [$limit, $limit],
            'one more than limit' => [$limit + 1, $limit],
            'far more than limit' => [$limit + 25, $limit],
        ];
    }

    private function runDepthLimitTest(int $queryDepth, int $maxDepth, array $schema): void
    {
        Config::modify()->set(Manager::class, 'schemas', $schema);
        $manager = new Manager('test_schema');
        $manager->configure();
        $request = $this->createGraphqlRequest($this->craftRecursiveQuery($queryDepth));
        $controller = $this->getFakeController($request, $manager);

        if ($queryDepth > $maxDepth) {
            $this->assertQueryError(
                $controller,
                $request,
                '/^Max query depth should be ' . $maxDepth . ' but got ' . $queryDepth . '\.$/'
            );
        } else {
            // Note that the depth limit is based on the depth of the QUERY, not of the RESULTS, so all we really care about
            // is that the query was successful, not what the results were.
            $this->assertQuerySuccess($controller, $request);
        }
    }

    private function runComplexityLimitTest(int $queryComplexity, int $maxComplexity, array $schema): void
    {
        Config::modify()->set(Manager::class, 'schemas', $schema);
        $manager = new Manager('test_schema');
        $manager->configure();
        $request = $this->createGraphqlRequest($this->craftComplexQuery($queryComplexity));
        $controller = $this->getFakeController($request, $manager);

        if ($queryComplexity > $maxComplexity) {
            $this->assertQueryError(
                $controller,
                $request,
                '/^Max query complexity should be ' . $maxComplexity . ' but got ' . $queryComplexity . '\.$/'
            );
        } else {
            // Note that the complexity limit is based on the complexity of the QUERY, not of the RESULTS, so all we really care about
            // is that the query was successful, not what the results were.
            $this->assertQuerySuccess($controller, $request);
        }
    }

    private function runNodeLimitTest(int $queryNodeCount, int $maxNodes, array $schema): void
    {
        Config::modify()->set(Manager::class, 'schemas', $schema);
        $manager = new Manager('test_schema');
        $manager->configure();
        $request = $this->createGraphqlRequest($this->craftComplexQuery($queryNodeCount - 1));
        $controller = $this->getFakeController($request, $manager);

        if ($queryNodeCount > $maxNodes) {
            $this->assertQueryError(
                $controller,
                $request,
                '/^GraphQL query body must not be longer than ' . $maxNodes . ' nodes\.$/'
            );
        } else {
            // Note that the complexity limit is based on the complexity of the QUERY, not of the RESULTS, so all we really care about
            // is that the query was successful, not what the results were.
            $this->assertQuerySuccess($controller, $request);
        }
    }

    private function craftRecursiveQuery(int $queryDepth): string
    {
        $query = 'query{ readSilverStripeHierarchicalObjects { nodes {';

        for ($i = 0; $i < $queryDepth; $i++) {
            if ($i % 3 === 0) {
                $query .= 'ID Title';
            } elseif ($i % 3 === 1) {
                $query .= ' Parent {';
            } elseif ($i % 3 === 2) {
                if ($i === $queryDepth - 1) {
                    $query .= 'ID Title';
                } else {
                    $query .= 'ID Title Children { nodes {';
                }
            }
        }

        $endsWith = strrpos($query, 'ID Title') === strlen($query) - strlen('ID Title');
        $query .= $endsWith ? '' : 'ID Title';
        // Add all of the closing brackets
        $numChars = array_count_values(str_split($query));
        for ($i = 0; $i < $numChars['{']; $i++) {
            $query .= '}';
        }

        return $query;
    }

    private function craftComplexQuery(int $queryComplexity): string
    {
        $query = 'query{ readOneSilverStripeHierarchicalObject { ID';

        // skip the first two complexity, because those are taken up by "readOneSilverStripeHierarchicalObject { ID" above
        for ($i = 0; $i < $queryComplexity - 2; $i++) {
            $query .= ' ID';
        }
        // Add all of the closing brackets
        $numChars = array_count_values(str_split($query));
        for ($i = 0; $i < $numChars['{']; $i++) {
            $query .= '}';
        }

        return $query;
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
     * @return \MockObject
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
