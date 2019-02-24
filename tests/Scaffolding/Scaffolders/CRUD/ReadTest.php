<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding\Scaffolders\CRUD;

use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\StringType;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Filters\EqualToFilter;
use SilverStripe\GraphQL\Filters\FilterRegistryInterface;
use SilverStripe\GraphQL\Filters\InFilter;
use SilverStripe\GraphQL\Filters\Registry;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\Security\Member;

class ReadTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class,
        RestrictedDataObjectFake::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        // Make sure we're only testing the native features
        foreach (Read::get_extensions() as $className) {
            Read::remove_extension($className);
        }
    }

    public function testReadOperationResolver()
    {
        $read = new Read(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $read->addToManager($manager);
        $scaffold = $read->scaffold($manager);

        DataObjectFake::get()->removeAll();

        $record1 = DataObjectFake::create();
        $record1->MyField = 'AA First';
        $ID1 = $record1->write();

        $record2 = DataObjectFake::create();
        $record2->MyField = 'ZZ Last';
        $ID2 = $record2->write();

        $record3 = DataObjectFake::create();
        $record3->MyField = 'BB Middle';
        $ID3 = $record3->write();

        $response = $scaffold['resolve'](
            null,
            [],
            [
                'currentUser' => Member::create(),
            ],
            new ResolveInfo([])
        );

        $this->assertArrayHasKey('edges', $response);
        /** @var DataList $edges */
        $edges = $response['edges'];
        $this->assertEquals([$ID1, $ID3, $ID2], $edges->column('ID'));
    }

    public function testReadOperationArgs()
    {
        $read = new Read(DataObjectFake::class);
        $read->addArg('MyField', 'String');
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'GraphQL_DataObjectFake']), 'GraphQL_DataObjectFake');
        $read->addToManager($manager);
        $scaffold = $read->scaffold($manager);

        $args = $scaffold['args'];
        $this->assertArrayHasKey('MyField', $args);
        $this->assertInstanceOf(StringType::class, $args['MyField']['type']);
    }

    public function testApplyConfig()
    {
        $mock = $this->getMockBuilder(Read::class)
            ->setConstructorArgs([DataObjectFake::class])
            ->setMethods(['addAllFilters', 'addFieldFilter', 'addDefaultFilters'])
            ->getMock();
        $mock->expects($this->once())
            ->method('addAllFilters');
        $mock->expects($this->once())
            ->method('addDefaultFilters')
            ->with('MyDefaultField');
        $mock->expects($this->once())
            ->method('addFieldFilter')
            ->with('MyCustomField', 'myfilter');

        $mock->applyConfig([
            'filters' => SchemaScaffolder::ALL
        ]);

        $mock->applyConfig([
            'filters' => [
                'MyDefaultField' => true,
                'MyCustomField' => [
                    'myfilter' => true,
                    'myfail' => false
                ],
            ]
        ]);
    }

    public function testApplyConfigExceptions()
    {
        $read = new Read(DataObjectFake::class);
        $this->expectException('InvalidArgumentException');
        $read->applyConfig([
            'filters' => [
                'MyFilter' => 'fail',
            ]
        ]);

        $this->expectException('InvalidArgumentException');
        $read->applyConfig([
            'filters' => [
                'MyFilter' => ['fail'],
            ]
        ]);

        $this->expectException('InvalidArgumentException');
        $read->applyConfig([
            'filters' => 'fail'
        ]);

    }

    public function testAddAllFilters()
    {
        $mock = $this->getMockBuilder(Read::class)
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
        $mock = $this->getMockBuilder(Read::class)
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

    public function testFilterCreation()
    {
        $manager = new Manager();
        $dataobject = new DataObjectScaffolder(DataObjectFake::class);
        $dataobject->addToManager($manager);
        $registry = new Registry();
        $registry->addFilter(new EqualToFilter(), 'myFilter');
        $registry->addFilter(new InFilter(), 'myListFilter');
        $read = new Read(DataObjectFake::class);
        $read->setUsePagination(false);
        $read->setFilterRegistry($registry);
        $read->addFieldFilter('MyField', 'myFilter');
        $read->addFieldFilter('Author__FirstName', 'myFilter');
        $read->addFieldFilter('MyInt', 'myListFilter');
        $read->addToManager($manager);

        $this->assertTrue($manager->hasType($read->getDataObjectTypeName() . 'FilterReadInputType'));
        $this->assertTrue($manager->hasType($read->getDataObjectTypeName() . 'ExcludeReadInputType'));
        /* @var InputObjectType $filterType */
        $filterType = $manager->getType($read->getDataObjectTypeName() . 'FilterReadInputType');
        /* @var InputObjectType $excludeType */
        $excludeType = $manager->getType($read->getDataObjectTypeName() . 'ExcludeReadInputType');

        // Filter input type
        $fields = $filterType->getFields();
        $this->assertArrayHasKey('MyField__myFilter', $fields);
        $this->assertInstanceOf(StringType::class, $fields['MyField__myFilter']->getType());
        $this->assertArrayHasKey('Author__FirstName__myFilter', $fields);
        $this->assertInstanceOf(StringType::class, $fields['Author__FirstName__myFilter']->getType());
        $this->assertArrayHasKey('MyInt__myListFilter', $fields);
        $this->assertInstanceOf(ListOfType::class, $fields['MyInt__myListFilter']->getType());
        $this->assertInstanceOf(IntType::class, $fields['MyInt__myListFilter']->getType()->getWrappedType());

        // Exclude input type
        $fields = $excludeType->getFields();
        $this->assertArrayHasKey('MyField__myFilter', $fields);
        $this->assertInstanceOf(StringType::class, $fields['MyField__myFilter']->getType());
        $this->assertArrayHasKey('Author__FirstName__myFilter', $fields);
        $this->assertInstanceOf(StringType::class, $fields['Author__FirstName__myFilter']->getType());
        $this->assertArrayHasKey('MyInt__myListFilter', $fields);
        $this->assertInstanceOf(ListOfType::class, $fields['MyInt__myListFilter']->getType());
        $this->assertInstanceOf(IntType::class, $fields['MyInt__myListFilter']->getType()->getWrappedType());

        // Check the integrity of the query object itself
        $read = $read->scaffold($manager);
        $this->assertArrayHasKey('args', $read);
        $this->assertArrayHasKey('Filter', $read['args']);
        $this->assertArrayHasKey('Exclude', $read['args']);
        $this->assertInstanceOf(InputObjectType::class, $read['args']['Filter']['type']);
        $this->assertInstanceOf(InputObjectType::class, $read['args']['Exclude']['type']);

        // Exceptions
        $this->expectException('Exception');
        $read = new Read(DataObjectFake::class);
        $read->setFilterRegistry($registry);
        $read->addFieldFilter('MyField', 'failFilter');
        $read->addToManager($manager);
        /* @var InputObjectType $filterType */
        $filterType = $manager->getType($read->getDataObjectTypeName() . 'FilterReadInputType');
        $filterType->getFields();

        $this->expectException('Exception');
        $read = new Read(DataObjectFake::class);
        $read->setFilterRegistry($registry);
        $read->addFieldFilter('Author__Surname', 'failFilter');
        $read->addToManager($manager);
        /* @var InputObjectType $filterType */
        $filterType = $manager->getType($read->getDataObjectTypeName() . 'FilterReadInputType');
        $filterType->getFields();
    }

    public function testResolverFilters()
    {
        // test createFieldFilters throws exceptions
        // test getResults calls apply() methods.
    }

}
