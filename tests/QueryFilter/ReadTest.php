<?php

namespace SilverStripe\GraphQL\Tests\QueryFilter;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\QueryFilter\DataObjectQueryFilter;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ReadOne;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FilterDataList;

class ReadTest extends SapphireTest
{

    protected static $extra_dataobjects = [
        DataObjectFake::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // Make sure we're only testing the native features
        foreach (ReadOne::get_extensions() as $className) {
            ReadOne::remove_extension($className);
        }
        foreach (Read::get_extensions() as $className) {
            Read::remove_extension($className);
        }
    }

    /**
     * @param $classToTest
     * @dataProvider provider
     */
    public function testApplyConfig($classToTest)
    {
        /* @var ReadOne|Read $read */
        $read = new $classToTest(DataObjectFake::class);
        $mock = $this->getMockBuilder(DataObjectQueryFilter::class)
            ->setConstructorArgs([DataObjectFake::class])
            ->setMethods(['addAllFilters'])
            ->getMock();
        $mock->expects($this->once())
            ->method('addAllFilters');

        $read->setQueryFilter($mock);
        $read->applyConfig([
            'filters' => SchemaScaffolder::ALL
        ]);

        $this->expectException('InvalidArgumentException');
        $read->applyConfig([
            'filters' => 'fail'
        ]);
    }

    /**
     * @param $classToTest
     * @dataProvider provider
     */
    public function testQueryFilterAddsToManager($classToTest)
    {
        /* @var ReadOne|Read $read */
        $read = new $classToTest(DataObjectFake::class);
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

    /**
     * @param $classToTest
     * @dataProvider provider
     */
    public function testQueryFilterScaffold($classToTest)
    {
        /* @var ReadOne|Read $read */
        $read = new $classToTest(DataObjectFake::class);
        if ($read instanceof Read) {
            $read->setUsePagination(false);
        }
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $read->addToManager($manager);
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

    /**
     * @param $classToTest
     * @dataProvider provider
     */
    public function testQueryFilterResolve($classToTest)
    {
        /* @var ReadOne|Read $read */
        $read = new $classToTest(DataObjectFake::class);
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

        /* @var ReadOne|Read $read */
        $read = new $classToTest(DataObjectFake::class);
        $mock = $this->getMockBuilder(DataObjectQueryFilter::class)
            ->setConstructorArgs([DataObjectFake::class])
            ->setMethods(['applyArgsToList'])
            ->getMock();
        $mock->expects($this->once())
            ->method('applyArgsToList')
            ->willReturn(new FilterDataList(DataObjectFake::class));
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

    /**
     * @return array
     */
    public function provider()
    {
        return [
            [Read::class],
            [ReadOne::class]
        ];
    }
}
