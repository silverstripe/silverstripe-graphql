<?php

namespace SilverStripe\GraphQL\Tests;

use Exception;
use PHPUnit_Framework_MockObject_MockBuilder;
use ReflectionClass;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Controller;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake;

class ControllerTest extends SapphireTest
{
    public function setUp()
    {
        parent::setUp();

        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        // Disable CORS Config by default.
        Controller::config()->set('cors', [ 'Enabled' => false ]);
    }

    public function testIndex()
    {
        $controller = new Controller();
        $manager = new Manager();
        $manager->addType($this->getType($manager), 'mytype');
        $manager->addQuery($this->getQuery($manager), 'myquery');
        Injector::inst()->registerService($manager);
        $response = $controller->index(new HTTPRequest('GET', ''));
        $this->assertFalse($response->isError());
    }

    public function testManagerIsPopulatedFromConfig()
    {
        Config::modify()->set(Controller::class, 'schema', [
            'types' => [
                'mytype' => TypeCreatorFake::class,
            ],
        ]);

        $manager = Manager::createFromConfig(
            Config::inst()->get(Controller::class, 'schema')
        );
        $this->assertNotNull(
            $manager->getType('mytype')
        );
    }

    public function testIndexWithException()
    {
        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        $kernel->setEnvironment(Kernel::LIVE);

        $controller = new Controller();
        /** @var Manager|PHPUnit_Framework_MockObject_MockBuilder $managerMock */
        $managerMock = $this->getMockBuilder(Manager::class)
            ->setMethods(['query'])
            ->getMock();

        $managerMock->method('query')
            ->will($this->throwException(new Exception('Failed')));

        Injector::inst()->registerService($managerMock, Manager::class);
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
        $controller = new Controller();
        /** @var Manager|PHPUnit_Framework_MockObject_MockBuilder $managerMock */
        $managerMock = $this->getMockBuilder(Manager::class)
            ->setMethods(['query'])
            ->getMock();

        $managerMock->method('query')
            ->will($this->throwException(new Exception('Failed')));

        Injector::inst()->registerService($managerMock, Manager::class);

        $response = $controller->index(new HTTPRequest('GET', ''));
        $this->assertFalse($response->isError());
        $responseObj = json_decode($response->getBody(), true);
        $this->assertNotNull($responseObj);
        $this->assertArrayHasKey('errors', $responseObj);
        $this->assertEquals('Failed', $responseObj['errors'][0]['message']);
        $this->assertArrayHasKey('trace', $responseObj['errors'][0]);
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
            'Allow-Origin' => 'localhost',
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
        $this->assertEquals('200', $response->getStatusCode());

        // Check returned headers.  A valid origin should return 4 headers.
        $this->assertEquals('localhost', $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertEquals('Authorization, Content-Type', $response->getHeader('Access-Control-Allow-Headers'));
        $this->assertEquals('GET, POST, OPTIONS', $response->getHeader('Access-Control-Allow-Methods'));
        $this->assertEquals(86400, $response->getHeader('Access-Control-Max-Age'));
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

    /**
     * @expectedException \SilverStripe\Control\HTTPResponse_Exception
     */
    public function testAddCorsHeadersOriginMissing()
    {
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
        $response = $controller->addCorsHeaders($request, $response);

        $this->assertTrue($response instanceof HTTPResponse);
        $this->assertEquals('403', $response->getStatusCode());
    }

    /**
     * @expectedException \SilverStripe\Control\HTTPResponse_Exception
     */
    public function testAddCorsHeadersResponseCORSDisabled()
    {
        Config::modify()->set(Controller::class, 'cors', [
            'Enabled' => false
        ]);

        $controller = new Controller();
        $request = new HTTPRequest('OPTIONS', '');
        $request->addHeader('Origin', 'localhost');
        $response = $controller->index($request);

        $this->assertTrue($response instanceof HTTPResponse);
        $this->assertEquals('405', $response->getStatusCode());
    }

    protected function getType(Manager $manager)
    {
        return (new TypeCreatorFake($manager))->toType();
    }

    protected function getQuery(Manager $manager)
    {
        return (new QueryCreatorFake($manager))->toArray();
    }
}
