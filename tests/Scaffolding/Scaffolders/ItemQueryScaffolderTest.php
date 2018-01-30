<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ItemQueryScaffolder;
use GraphQL\Type\Definition\ObjectType;

class ItemQueryScaffolderTest extends SapphireTest
{
    public function testItemQueryScaffolder()
    {
        /** @var Manager $observer */
        $observer = $this->getMockBuilder(Manager::class)
            ->setMethods(['addQuery'])
            ->getMock();
        $scaffolder = new ItemQueryScaffolder('testQuery', 'test');
        $scaffolder->addArgs(['Test' => 'String']);
        $manager = new Manager();
        $manager->addType($o = new ObjectType([
            'name' => 'test',
            'fields' => [],
        ]));
        $o->Test = true;

        $scaffold = $scaffolder->scaffold($manager);

        $this->assertEquals('testQuery', $scaffold['name']);
        $this->assertArrayHasKey('Test', $scaffold['args']);
        $this->assertTrue(is_callable($scaffold['resolve']));
        $this->assertTrue($scaffold['type']->Test);

        $observer->expects($this->once())
            ->method('addQuery')
            ->with(
                function ($arg) use ($scaffold) {
                    return $arg() === $scaffold;
                },
                $this->equalTo('testQuery')
            );

        $scaffolder->addToManager($observer);
    }
}
