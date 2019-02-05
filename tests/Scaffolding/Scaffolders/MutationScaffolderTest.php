<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\Scaffolding;

use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;

class MutationScaffolderTest extends SapphireTest
{
    public function testMutationScaffolder()
    {
        /** @var Manager $observer */
        $observer = $this->getMockBuilder(Manager::class)
            ->setMethods(['addMutation','getType'])
            ->getMock();
        $observer->method('getType')
            ->will($this->returnValue(new ObjectType(['name' => 'test'])));

        $scaffolder = new MutationScaffolder('testMutation', 'test');
        $scaffolder->setDescription('My description');
        $scaffolder->addArgs(['Test' => 'String']);
        $manager = new Manager();
        $manager->addType($o = new ObjectType([
            'name' => 'test',
            'fields' => [],
        ]));
        $o->Test = true;

        $scaffold = $scaffolder->scaffold($manager);

        $this->assertEquals('testMutation', $scaffold['name']);
        $this->assertEquals('My description', $scaffold['description']);
        $this->assertArrayHasKey('Test', $scaffold['args']);
        $this->assertTrue(is_callable($scaffold['resolve']));
        $this->assertTrue($scaffold['type']->Test);

        $observer->expects($this->once())
            ->method('addMutation')
            ->with(
                function ($arg) use ($scaffold) {
                    return $arg() === $scaffold;
                },
                $this->equalTo('testMutation')
            );

        $scaffolder->addToManager($observer);
    }
}
