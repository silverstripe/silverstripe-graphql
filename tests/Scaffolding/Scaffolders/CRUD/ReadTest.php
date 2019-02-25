<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding\Scaffolders\CRUD;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\StringType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\QueryFilter\DataObjectQueryFilter;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\ORM\DataList;
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
        $read = new Read(DataObjectFake::class);
        $mock = $this->getMockBuilder(DataObjectQueryFilter::class)
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

        $read->setQueryFilter($mock);
        $read->applyConfig([
            'filters' => SchemaScaffolder::ALL
        ]);

        $read->applyConfig([
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

    public function testQueryFilterAddsToManager()
    {
        $read = new Read(DataObjectFake::class);
        $mock = $this->getMockBuilder(DataObjectQueryFilter::class)
            ->setConstructorArgs([DataObjectFake::class])
            ->setMethods(['getInputType'])
            ->getMock();
        $mock->expects($this->never())
            ->method('getInputType');
        $read->setQueryFilter($mock);

        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $read->addToManager($manager);

        $mock = $this->getMockBuilder(DataObjectQueryFilter::class)
            ->setConstructorArgs([DataObjectFake::class])
            ->setMethods(['getInputType'])
            ->getMock();
        $mock->expects($this->exactly(2))
            ->method('getInputType')
            ->willReturn(new InputObjectType(['name' => 'test']));
        $read->setQueryFilter($mock);
        $read->queryFilter()->addAllFilters();

        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $read->addToManager($manager);
    }

    public function testQueryFilterScaffold()
    {
        $read = new Read(DataObjectFake::class);
        $read->setUsePagination(false);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $data = $read->scaffold($manager);
        $this->assertArrayHasKey('args', $data);
        $this->assertArrayNotHasKey(Read::FILTER, $data['args']);
        $this->assertArrayNotHasKey(Read::EXCLUDE, $data['args']);

        $read->queryFilter()->addAllFilters();
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $read->addToManager($manager);
        $data = $read->scaffold($manager);
        $this->assertArrayHasKey('args', $data);
        $this->assertArrayHasKey(Read::FILTER, $data['args']);
        $this->assertArrayHasKey(Read::EXCLUDE, $data['args']);
        $this->assertInstanceOf(InputObjectType::class, $data['args'][Read::FILTER]['type']);
        $this->assertInstanceOf(InputObjectType::class, $data['args'][Read::EXCLUDE]['type']);
    }

    public function testQueryFilterResolve()
    {
        $read = new Read(DataObjectFake::class);
        $mock = $this->getMockBuilder(DataObjectQueryFilter::class)
            ->setConstructorArgs([DataObjectFake::class])
            ->setMethods(['applyArgsToList'])
            ->getMock();
        $mock->expects($this->never())
            ->method('applyArgsToList');
        $read->setQueryFilter($mock);

        $read->resolve(
            null,
            [],
            ['currentUser' => null],
            new ResolveInfo([])
        );

        $read = new Read(DataObjectFake::class);
        $mock = $this->getMockBuilder(DataObjectQueryFilter::class)
            ->setConstructorArgs([DataObjectFake::class])
            ->setMethods(['applyArgsToList'])
            ->getMock();
        $mock->expects($this->once())
            ->method('applyArgsToList');
        $read->setQueryFilter($mock);
        $read->queryFilter()->addAllFilters();

        $read->resolve(
            null,
            [
                'Filter' => [
                    'MyField__eq' => 'test'
                ],
                'Exclude' => [
                    'MyInt__gt' => 4
                ]
            ],
            ['currentUser' => null],
            new ResolveInfo([])
        );
    }
}
