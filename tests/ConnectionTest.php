<?php

namespace SilverStripe\GraphQL\Tests;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Pagination\SortInputTypeCreator;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\PaginatedQueryFake;
use SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake;
use SilverStripe\ORM\ArrayList;
use InvalidArgumentException;

class ConnectionTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class
    ];

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Manager
     */
    private $manager;

    public function setUp()
    {
        parent::setUp();

        $config = [
            'types' => [
                'TypeCreatorFake' => TypeCreatorFake::class,
            ],
            'queries' => [
                'paginatedquery' => PaginatedQueryFake::class,
            ],
        ];

        $this->manager = Manager::createFromConfig($config);
        $this->connection = Connection::create('testConnection')
            ->setConnectionType(function () {
                return $this->manager->getType('TypeCreatorFake');
            })
            ->setConnectionResolver(function () {
                $result = new ArrayList();
                $result->push([
                    'ID' => 10,
                    'MyValue' => 'testMyValidResolverValue'
                ]);

                return $result;
            });


        $fakeObject = new DataObjectFake([
            'MyField' => 'object1'
        ]);

        $fakeObject->write();

        $fakeObject = new DataObjectFake([
            'MyField' => 'object2'
        ]);

        $fakeObject->write();
    }

    public function testResolveList()
    {
        $list = DataObjectFake::get();

        $connection = Connection::create('testFake')
            ->setConnectionType(function () {
                return $this->manager->getType('TypeCreatorFake');
            });

        $result = $connection->resolveList($list, []);

        $this->assertEquals(2, $result['edges']->count());
        $this->assertEquals(2, $result['pageInfo']['totalCount']);
        $this->assertFalse($result['pageInfo']['hasNextPage']);
        $this->assertFalse($result['pageInfo']['hasPreviousPage']);

        $this->assertEquals('object1', $result['edges']->first()->MyField);

        // test a resolution with the limit
        $result = $connection->resolveList($list, ['limit' => 1]);

        $this->assertEquals(1, $result['edges']->count());
        $this->assertEquals(2, $result['pageInfo']['totalCount']);
        $this->assertTrue($result['pageInfo']['hasNextPage']);
        $this->assertFalse($result['pageInfo']['hasPreviousPage']);
    }

    public function testResolveListSort()
    {
        $list = DataObjectFake::get();

        $connection = Connection::create('testFake')
            ->setSortableFields(['MyField'])
            ->setConnectionType(function () {
                return $this->manager->getType('TypeCreatorFake');
            });

        // test a resolution with the limit
        $result = $connection->resolveList(
            $list,
            ['sortBy' => [['field' => 'MyField', 'direction' => 'DESC']]]
        );

        $this->assertEquals('object2', $result['edges']->first()->MyField);
        $this->assertEquals('object1', $result['edges']->last()->MyField);

        $result = $connection->resolveList(
            $list,
            ['sortBy' => [['field' => 'MyField', 'direction' => 'ASC']]]
        );

        $this->assertEquals('object1', $result['edges']->first()->MyField);
    }

    public function testResolveListSortWithCustomMapping()
    {
        $list = DataObjectFake::get();

        $connection = Connection::create('testFake')
            ->setSortableFields(['MyFieldAlias' => 'MyField'])
            ->setConnectionType(function () {
                return $this->manager->getType('TypeCreatorFake');
            });

        // test a resolution with the limit
        $result = $connection->resolveList(
            $list,
            ['sortBy' => [['field' => 'MyFieldAlias', 'direction' => 'DESC']]]
        );

        $this->assertEquals('object2', $result['edges']->first()->MyField);
        $this->assertEquals('object1', $result['edges']->last()->MyField);

        $result = $connection->resolveList(
            $list,
            ['sortBy' => [['field' => 'MyFieldAlias', 'direction' => 'ASC']]]
        );

        $this->assertEquals('object1', $result['edges']->first()->MyField);
    }

    public function testSortByInvalidColumnThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);

        $list = DataObjectFake::get();

        $connection = Connection::create('testFake')
            ->setSortableFields(['MyField'])
            ->setConnectionType(function () {
                return $this->manager->getType('TypeCreatorFake');
            });

        // test a resolution with the limit
        $connection->resolveList(
            $list,
            ['sortBy' => [['field' => 'ID', 'direction' => 'DESC']]]
        );
    }

    public function testToType()
    {
        $type = $this->connection->toType();

        $this->assertInstanceOf(FieldDefinition::class, $type->getField('pageInfo'), 'pageInfo should exist');
        $this->assertInstanceOf(FieldDefinition::class, $type->getField('edges'), 'edges should exist');
    }

    public function testGetEdgeTypeResolver()
    {
        $edge = $this->connection->getEdgeType();

        $this->assertInstanceOf(ObjectType::class, $edge, 'Edge should be an ObjectType');
        $node = $edge->getField('node');

        $this->assertInstanceOf(FieldDefinition::class, $node, 'Node should exist');
    }

    public function testCollectionResolves()
    {
        $resolve = $this->connection->resolve(null, [], [], new ResolveInfo([]));
        $item = $resolve['edges']->first();
        $this->assertEquals('testMyValidResolverValue', $item['MyValue']);
    }

    public function testCollectionWithLimits()
    {
        $list = DataObjectFake::get();

        $connection = Connection::create('testFakeConnection')
            ->setMaximumLimit(1)
            ->setConnectionType(function () {
                return $this->manager->getType('TypeCreatorFake');
            });

        // test a resolution with the limit
        $result = $connection->resolveList(
            $list,
            ['offset' => 1]
        );

        $this->assertEquals(1, $result['edges']->count(), 'We set maximum limit of 1');
        $this->assertTrue($result['pageInfo']['hasPreviousPage']);
    }

    public function testSortInputTypeRendersType()
    {
        $type = new SortInputTypeCreator('TestSort');
        $type->setSortableFields(['ID', 'Title']);

        $built = $type->toType();
        $this->assertInstanceOf(InputObjectField::class, $built->getField('field'));
    }

    public function testArgsAsArray()
    {
        $connection = Connection::create('testFakeConnection')
            ->setConnectionType(function () {
                return $this->manager->getType('TypeCreatorFake');
            })
            ->setArgs([
                'arg1' => [
                    'type' => Type::int()
                ]
            ]);

        $this->assertArrayHasKey('arg1', $connection->args());
    }

    public function testArgsAsCallable()
    {
        $connection = Connection::create('testFakeConnection')
            ->setConnectionType(function () {
                return $this->manager->getType('TypeCreatorFake');
            })
            ->setArgs(function () {
                return [
                    'arg1' => [
                        'type' => Type::int()
                    ]
                ];
            });

        $this->assertArrayHasKey('arg1', $connection->args());
    }
}
