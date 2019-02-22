<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\Scaffolding;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ItemQueryScaffolder;
use SilverStripe\GraphQL\Tests\Fake\FakePermissionChecker;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class ItemQueryScaffolderTest extends SapphireTest
{
    public function testItemQueryScaffolder()
    {
        /** @var Manager $observer */
        $observer = $this->getMockBuilder(Manager::class)
            ->setMethods(['addQuery'])
            ->getMock();
        $scaffolder = new ItemQueryScaffolder('testQuery', 'test');
        $scaffolder->setDescription('My description');
        $scaffolder->addArgs(['Test' => 'String']);
        $manager = new Manager();
        $manager->addType($o = new ObjectType([
            'name' => 'test',
            'fields' => [],
        ]));
        $o->Test = true;

        $scaffold = $scaffolder->scaffold($manager);

        $this->assertEquals('testQuery', $scaffold['name']);
        $this->assertEquals('My description', $scaffold['description']);
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

    /**
     * @dataProvider permissionProvider
     * @param bool|null $allow
     */
    public function testPermissionCheck($allow)
    {
        $resolver = function () {
            return new ArrayData(['Foo' => 'Bar']);
        };
        $manager = new Manager();
        $manager->addType(new ObjectType([
            'name' => 'testType',
            'fields' => [],
        ]));

        $scaffolder = new ItemQueryScaffolder('testQuery', 'testType', $resolver);
        if ($allow !== null) {
            $scaffolder->setPermissionChecker(new FakePermissionChecker($allow));
        }

        $scaffolder->addToManager($manager);
        $arr = $scaffolder->scaffold($manager);
        if ($allow === false) {
            $this->expectException('Exception');
        }
        $result = $arr['resolve'](null, [], ['currentUser' => null], new ResolveInfo([]));
        if ($allow !== false) {
            $this->assertNotNull($result);
            $this->assertEquals('Bar', $result->Foo);
        }
    }

    /**
     * @return array
     */
    public function permissionProvider()
    {
        return [
            [null],
            [true],
            [false]
        ];
    }
}
