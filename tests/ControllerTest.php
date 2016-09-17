<?php

namespace SilverStripe\Tests\GraphQL;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Controller;
use SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake;
use SilverStripe\Core\Config\Config;
use ReflectionClass;

class ControllerTest extends SapphireTest
{

    public function testIndex()
    {
        $controller = new Controller();
        $manager = new Manager();
        $manager->addType(TypeCreatorFake::class, 'mytype');
        $manager->addQuery(QueryCreatorFake::class, 'myquery');
        $controller->setManager($manager);
        $response = $controller->index(new HTTPRequest('GET', ''));
        $this->assertFalse($response->isError());
    }

    public function testGetGetManagerPopulatesFromConfig()
    {
        Config::inst()->update('SilverStripe\GraphQL', 'schema', [
            'types' => [
                'mytype' => TypeCreatorFake::class,
            ]
        ]);

        $controller = new Controller();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getManager');
        $method->setAccessible(true);
        $manager = $method->invoke($controller);
        $this->assertInstanceOf(
            TypeCreatorFake::class,
            $manager->getType('mytype')
        );
    }

}
