<?php

namespace SilverStripe\Tests\GraphQL;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use Chillu\GraphQL\Manager;
use Chillu\GraphQL\Controller;
use Chillu\GraphQL\Tests\Fake\TypeCreatorFake;
use Chillu\GraphQL\Tests\Fake\QueryCreatorFake;
use SilverStripe\Core\Config\Config;
use ReflectionClass;

class ControllerTest extends SapphireTest
{
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
        Config::inst()->remove('Chillu\GraphQL', 'schema');
        Config::inst()->update('Chillu\GraphQL', 'schema', [
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

    protected function getType(Manager $manager)
    {
        return (new TypeCreatorFake($manager))->toType();
    }

    protected function getQuery(Manager $manager)
    {
        return (new QueryCreatorFake($manager))->toArray();
    }
}
