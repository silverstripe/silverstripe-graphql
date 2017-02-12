<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class MutationScaffolderTest extends SapphireTest
{
    public function testMutationScaffolder()
    {
        $observer = $this->getMockBuilder(Manager::class)
            ->setMethods(['addMutation'])
            ->getMock();
        $scaffolder = new MutationScaffolder('testMutation', 'test');
        $scaffolder->addArgs(['Test' => 'String']);
        $scaffold = $scaffolder->scaffold($manager = new Manager());
        $manager->addType($o = new ObjectType([
            'name' => 'test',
            'fields' => [],
        ]));
        $o->Test = true;

        $this->assertEquals('testMutation', $scaffold['name']);
        $this->assertArrayHasKey('Test', $scaffold['args']);
        $this->assertTrue(is_callable($scaffold['resolve']));
        $this->assertTrue($scaffold['type']()->Test);

        $observer->expects($this->once())
            ->method('addMutation')
            ->with(
                $this->equalTo($scaffold),
                $this->equalTo('testMutation')
            );

        $scaffolder->addToManager($observer);
    }
}
