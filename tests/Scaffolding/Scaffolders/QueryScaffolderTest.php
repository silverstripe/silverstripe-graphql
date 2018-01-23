<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\QueryScaffolder;
use GraphQL\Type\Definition\ObjectType;

class QueryScaffolderTest extends SapphireTest
{
    public function testQueryScaffolderUnpaginated()
    {
        /** @var Manager $observer */
        $observer = $this->getMockBuilder(Manager::class)
            ->setMethods(['addQuery'])
            ->getMock();
        $scaffolder = new QueryScaffolder('testQuery', 'test');
        $scaffolder->setUsePagination(false);
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
        $this->assertTrue($scaffold['type']->getWrappedType()->Test);

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

    public function testQueryScaffolderPaginated()
    {
        $scaffolder = new QueryScaffolder('testQuery', 'test');
        $scaffolder->setUsePagination(true);
        $scaffolder->addArgs(['Test' => 'String']);
        $scaffolder->addSortableFields(['test']);
        $manager = new Manager();
        $manager->addType($o = new ObjectType([
            'name' => 'test',
            'fields' => [],
        ]));
        $o->Test = true;
        $scaffold = $scaffolder->scaffold($manager);
        $config = $scaffold['type']->config;

        $this->assertEquals('testQueryConnection', $config['name']);
        $this->assertArrayHasKey('pageInfo', $config['fields']());
        $this->assertArrayHasKey('edges', $config['fields']());
    }

    public function testQueryScaffolderApplyConfig()
    {
        /** @var QueryScaffolder $mock */
        $mock = $this->getMockBuilder(QueryScaffolder::class)
            ->setConstructorArgs(['testQuery', 'testType'])
            ->setMethods(['addSortableFields', 'setUsePagination'])
            ->getMock();
        $mock->expects($this->once())
            ->method('addSortableFields')
            ->with(['Test1', 'Test2']);
        $mock->expects($this->once())
            ->method('setUsePagination')
            ->with(false);

        $mock->applyConfig([
            'sortableFields' => ['Test1', 'Test2'],
            'paginate' => false,
        ]);
    }

    public function testQueryScaffolderApplyConfigThrowsOnBadSortableFields()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/sortableFields must be an array/');
        $scaffolder = new QueryScaffolder('testQuery', 'testType');
        $scaffolder->applyConfig([
            'sortableFields' => 'fail',
        ]);
    }
}
