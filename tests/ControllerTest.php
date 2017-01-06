<?php

namespace SilverStripe\GraphQL\Tests;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Auth\Handler;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Controller;
use SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Member;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use ReflectionClass;
use Exception;

class ControllerTest extends SapphireTest
{
    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        Config::inst()->nest();
        Config::inst()->update('SilverStripe\\GraphQL', 'authenticators', null);
    }

    public function testIndex()
    {
        $controller = new Controller();
        $manager = new Manager();
        $manager->addType($this->getType($manager), 'mytype');
        $manager->addQuery($this->getQuery($manager), 'myquery');
        $controller->setManager($manager);
        $response = $controller->index(new HTTPRequest('GET', ''));
        $this->assertFalse($response->isError());
    }

    public function testGetGetManagerPopulatesFromConfig()
    {
        Config::inst()->remove('SilverStripe\GraphQL', 'schema');
        Config::inst()->update('SilverStripe\GraphQL', 'schema', [
            'types' => [
                'mytype' => TypeCreatorFake::class,
            ],
        ]);

        $controller = new Controller();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getManager');
        $method->setAccessible(true);
        $manager = $method->invoke($controller);
        $this->assertNotNull(
            $manager->getType('mytype')
        );
    }

    public function testIndexWithException()
    {
        Config::inst()->update('SilverStripe\\Control\\Director', 'environment_type', 'live');

        $controller = new Controller();
        $managerMock = $this->getMockBuilder(Schema::class)
            ->setMethods(['query'])
            ->setConstructorArgs([
                ['query' => new ObjectType([
                    'name' => 'Query',
                    'fields' => []
                ])]
            ])
            ->getMock();

        $managerMock->method('query')
            ->will($this->throwException(new Exception('Failed')));

        $controller->setManager($managerMock);
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
        Config::inst()->update('SilverStripe\\Control\\Director', 'environment_type', 'dev');

        $controller = new Controller();
        $managerMock = $this->getMockBuilder(Schema::class)
            ->setMethods(['query'])
            ->setConstructorArgs([
                ['query' => new ObjectType([
                    'name' => 'Query',
                    'fields' => []
                ])]
            ])
            ->getMock();

        $managerMock->method('query')
            ->will($this->throwException(new Exception('Failed')));

        $controller->setManager($managerMock);
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
        $controller = new Controller;
        $controller->setManager(new Manager);
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
        Config::inst()->update('SilverStripe\\GraphQL', 'authenticators', [
            ['class' => $authenticator]
        ]);

        $controller = new Controller;
        $manager = new Manager;
        $controller->setManager($manager);
        $manager->addType($this->getType($manager), 'mytype');
        $manager->addQuery($this->getQuery($manager), 'myquery');

        $response = $controller->index(new HTTPRequest('GET', ''));

        $assertion = ($shouldFail) ? 'assertContains' : 'assertNotContains';
        $this->{$assertion}('Authentication failed', $response->getBody());
    }

    /**
     * {@inheritDoc}
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
                true,
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        Config::inst()->unnest();
        parent::tearDown();
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
