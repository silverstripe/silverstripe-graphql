<?php

namespace SilverStripe\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\PaginatedQueryFake;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;

class ConnectionTest extends SapphireTest
{
    protected $extraDataObjects = array(
        'SilverStripe\GraphQL\Tests\Fake\DataObjectFake'
    );

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
        $config = [
            'types' => [
                'TypeCreatorFake' => TypeCreatorFake::class,
            ],
            'queries' => [
                'paginatedquery' => PaginatedQueryFake::class,
            ],
        ];

        $this->manager = Manager::createFromConfig($config);
        $this->connection = new Connection([
            'name' => 'testConnection',
            'nodeType' => $this->manager->getType('TypeCreatorFake'),
            'resolveConnection' => function() {
                $result = new ArrayList();
                $result->push([
                    'ID' => 10,
                    'MyValue' => 'testMyValidResolverValue'
                ]);

                return $result;
            }
        ]);

        parent::setUp();

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

        $connection = new Connection([
            'name' => 'testFake',
            'sortableFields' => ['MyField'],
            'nodeType' =>  $this->manager->getType('TypeCreatorFake')
        ]);

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

        $connection = new Connection([
            'name' => 'testFake',
            'sortableFields' => ['MyField'],
            'nodeType' =>  $this->manager->getType('TypeCreatorFake')
        ]);

        // test a resolution with the limit
        $result = $connection->resolveList($list, ['sort' => 'MyField', 'sortDirection' => 'DESC']);
        $this->assertEquals('object2', $result['edges']->first()->MyField);
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
}
