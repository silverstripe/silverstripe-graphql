<?php


namespace SilverStripe\GraphQL\Tests\Schema;

use GraphQL\Type\Schema as GraphQLSchema;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\EventDispatcher\Dispatch\Dispatcher;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageCreator;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageInterface;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\GraphQL\Schema\Type\Type;

class SchemaBuilderTest extends SapphireTest
{
    /**
     * @throws SchemaNotFoundException
     */
    public function testFetch()
    {
        $id = uniqid();
        $this->assertNull(SchemaBuilder::singleton()->getSchema('my-schema-' . $id));
        Injector::inst()->load([
            CodeGenerationStore::class => [
                'properties' => [
                    'rootDir' => __DIR__
                ]
            ],
        ]);
        Config::inst()->merge(
            Injector::class,
            Dispatcher::class,
            [
                'properties' => [
                    'handlers' => [
                        'graphqlSchemaBuild' => [
                            'off' => ['graphqlSchemaBuild']
                        ],
                    ],
                ],
            ]
        );

        $schema = Schema::create('my-schema-' . $id);
        $schema->addQuery(Query::create('myQuery', 'TestType'));
        SchemaBuilder::singleton()->build($schema, true);

        $schema = SchemaBuilder::singleton()->getSchema('my-schema-' . $id);
        $this->assertInstanceOf(GraphQLSchema::class, $schema);
    }

    public function testBoot()
    {
        Config::modify()->merge(Schema::class, 'schemas', [
            'my-schema' => [
                'types' => [
                    'MyType' => [
                        'fields' => [
                            'foo' => 'String',
                        ],
                    ],
                ],
            ],
        ]);

        $schema = SchemaBuilder::singleton()->boot('my-schema');

        $type = $schema->getType('MyType');
        $this->assertInstanceOf(Type::class, $type);
    }

    public function testBuild()
    {
        $fakeStore = $this->createMock(SchemaStorageInterface::class);
        $creator = $this->createMock(SchemaStorageCreator::class);
        $creator->expects($this->once())
            ->method('createStore')
            ->willReturn($fakeStore);
        $builder = new SchemaBuilder($creator);
        $schema = Schema::create('test');
        $schema->addQuery(Query::create('myQuery')->setType('TestType'));
        $fakeStore->expects($this->once())
            ->method('persistSchema')
            ->with($this->equalTo($schema->getStoreableSchema()));

        $builder->build($schema);
    }

    public function testRead()
    {
        $fakeStore = $this->createMock(SchemaStorageInterface::class);
        $creator = $this->createMock(SchemaStorageCreator::class);
        $creator->expects($this->exactly(2))
            ->method('createStore')
            ->willReturn($fakeStore);
        $builder = new SchemaBuilder($creator);
        $schema = Schema::create('test');
        $schema->addQuery(Query::create('myQuery')->setType('TestType'));
        $fakeStore->expects($this->exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);
        $fakeStore->expects($this->once())
            ->method('getConfig');

        $builder->getConfig('test');
        $builder->getConfig('test');
    }
}
