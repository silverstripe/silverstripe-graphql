<?php

namespace SilverStripe\GraphQL\Tests\Filters;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\StringType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\QueryFilter\DataObjectQueryFilter;
use SilverStripe\GraphQL\QueryFilter\FieldFilterRegistry;
use SilverStripe\GraphQL\QueryFilter\Filters\EqualToFilter;
use SilverStripe\GraphQL\QueryFilter\Filters\GreaterThanFilter;
use SilverStripe\GraphQL\QueryFilter\Filters\InFilter;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FilterDataList;
use SilverStripe\ORM\FieldType\DBVarchar;

class DataObjectQueryFilterTest extends SapphireTest
{
    public function testAddAllFilters()
    {
        $mock = $this->getMockBuilder(DataObjectQueryFilter::class)
            ->setConstructorArgs([DataObjectFake::class])
            ->setMethods(['addDefaultFilters'])
            ->getMock();
        $mock->expects($this->exactly(7))
            ->method('addDefaultFilters')
            ->withConsecutive(
                ['ID'],
                ['ClassName'],
                ['LastEdited'],
                ['Created'],
                ['MyField'],
                ['MyInt'],
                ['AuthorID']
            );
        $mock->addAllFilters();
    }

    public function testAddDefaultFilters()
    {
        $defaults = DBVarchar::config()->get('default_filters');
        $with = array_map(function ($default) {
            return ['MyField', $default];
        }, $defaults);
        $mock = $this->getMockBuilder(DataObjectQueryFilter::class)
            ->setConstructorArgs([DataObjectFake::class])
            ->setMethods(['addFieldFilter'])
            ->getMock();
        $mock->expects($this->exactly(sizeof($defaults)))
            ->method('addFieldFilter')
            ->withConsecutive(...$with);

        $mock->addDefaultFilters('MyField');

        $this->expectException('InvalidArgumentException');
        $mock->addDefaultFilters('Fail');
    }

    public function testExists()
    {
        $filter = new DataObjectQueryFilter(DataObjectFake::class);
        $this->assertFalse($filter->exists());

        $filter->addAllFilters();
        $this->assertTrue($filter->exists());
    }

    public function testFilterCreation()
    {
        $registry = new FieldFilterRegistry();
        $registry->addFilter(new EqualToFilter(), 'myFilter');
        $registry->addFilter(new InFilter(), 'myListFilter');

        $filter = new DataObjectQueryFilter(DataObjectFake::class);
        $filter->setFilterRegistry($registry);

        $filter->addFieldFilter('MyField', 'myFilter');
        $filter->addFieldFilter('Author__FirstName', 'myFilter');
        $filter->addFieldFilter('MyInt', 'myListFilter');
        /* @var InputObjectType $filterType */
        $filterType = $filter->getInputType('MyFilter');

        // Filter input type
        $fields = $filterType->getFields();
        $this->assertArrayHasKey('MyField__myFilter', $fields);
        $this->assertInstanceOf(StringType::class, $fields['MyField__myFilter']->getType());
        $this->assertArrayHasKey('Author__FirstName__myFilter', $fields);
        $this->assertInstanceOf(StringType::class, $fields['Author__FirstName__myFilter']->getType());
        $this->assertArrayHasKey('MyInt__myListFilter', $fields);
        $this->assertInstanceOf(ListOfType::class, $fields['MyInt__myListFilter']->getType());
        $this->assertInstanceOf(IntType::class, $fields['MyInt__myListFilter']->getType()->getWrappedType());

        // Exceptions
        $this->expectException('Exception');
        $filter = new DataObjectQueryFilter(DataObjectFake::class);
        $filter->setFilterRegistry($registry);
        $filter->addFieldFilter('MyField', 'failFilter');
        /* @var InputObjectType $filterType */
        $filterType = $filter->getInputType('MyFilter');
        $filterType->getFields();

        $this->expectException('Exception');
        $read = new DataObjectQueryFilter(DataObjectFake::class);
        $read->setFilterRegistry($registry);
        $read->addFieldFilter('Author__Surname', 'failFilter');
        /* @var InputObjectType $filterType */
        $filterType = $filter->getInputType('TestFilter');
        $filterType->getFields();
    }

    public function testResolverFilters()
    {
        $filter = new DataObjectQueryFilter(DataObjectFake::class);
        $registry = new FieldFilterRegistry();
        $registry->addFilter(new EqualToFilter(), 'eq');
        $registry->addFilter(new GreaterThanFilter(), 'gt');
        $filter->setFilterRegistry($registry);

        $filter->addFieldFilter('MyField', 'eq');
        $filter->addFieldFilter('MyInt', 'gt');
        $params = [
            'Filter' => [
                'MyField__eq' => 'match',
            ],
            'Exclude' => [
                'MyInt__gt' => 10
            ],
        ];

        $list = $filter->applyArgsToList(new FilterDataList(DataObjectFake::class), []);
        $this->assertInstanceOf(FilterDataList::class, $list);

        $this->assertNull($list->filterField);
        $this->assertNull($list->filterValue);
        $this->assertNull($list->excludeField);
        $this->assertNull($list->excludeValue);

        $list = $filter->applyArgsToList(new FilterDataList(DataObjectFake::class), $params);
        $this->assertEquals('MyField:ExactMatch', $list->filterField);
        $this->assertEquals('match', $list->filterValue);
        $this->assertEquals('MyInt:GreaterThan', $list->excludeField);
        $this->assertEquals(10, $list->excludeValue);

        $params = [
            'Filter' => [
                'MyInt__gt' => 4,
            ],
            'Exclude' => [
                'MyField__eq' => 'not match'
            ],
        ];

        $list = $filter->applyArgsToList(new FilterDataList(DataObjectFake::class), $params);
        $this->assertInstanceOf(FilterDataList::class, $list);
        $this->assertEquals('MyInt:GreaterThan', $list->filterField);
        $this->assertEquals(4, $list->filterValue);
        $this->assertEquals('MyField:ExactMatch', $list->excludeField);
        $this->assertEquals('not match', $list->excludeValue);

        $this->expectException('InvalidArgumentException');
        $filter->applyArgsToList(
            new FilterDataList(DataObjectFake::class),
            [
                'Filter' => [
                    'MyInt' => 'test'
                ]
            ]
        );

        $this->expectException('InvalidArgumentException');
        $filter->applyArgsToList(
            new FilterDataList(DataObjectFake::class),
            [
                'Filter' => [
                    '__MyInt' => 'test'
                ]
            ]
        );

        $this->expectException('InvalidArgumentException');
        $filter->applyArgsToList(
            new FilterDataList(DataObjectFake::class),
            [
                'Filter' => [
                    'MyInt__fail' => 'test'
                ]
            ]
        );
    }
}
