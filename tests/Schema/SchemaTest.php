<?php

namespace SilverStripe\GraphQL\Tests\Schema;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\ModelCreator;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Mutation;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageInterface;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaContext;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\GraphQL\Schema\Type\InterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\Scalar;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\Schema\Type\UnionType;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;

class SchemaTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();
        // Kill the global schema
        Config::modify()->set(Schema::class, 'schemas', [
            Schema::ALL => [
                'src' => null,
            ]
        ]);
    }

    public function testConstructor()
    {
        $schema = $this->buildSchema('test');
        $this->assertEquals('test', $schema->getSchemaKey());
        $this->assertInstanceOf(SchemaContext::class, $schema->getSchemaContext());
    }

    public function testApplyConfig()
    {
        $mock = $this->getMockBuilder(Schema::class)
            ->setConstructorArgs(['test', $this->createSchemaContext()])
            ->setMethods(['addType', 'addInterface', 'addUnion', 'addModel', 'addEnum', 'addScalar'])
            ->getMock();
        $mock
            ->expects($this->exactly(3))
            ->method('addType')
            ->with($this->isInstanceOf(Type::class));

        $mock
            ->expects($this->once())
            ->method('addInterface')
            ->with($this->isInstanceOf(InterfaceType::class));
        $mock
            ->expects($this->once())
            ->method('addUnion')
            ->with($this->isInstanceOf(UnionType::class));
        $mock
            ->expects($this->exactly(2))
            ->method('addModel')
            ->with($this->isInstanceOf(ModelType::class));
        $mock
            ->expects($this->once())
            ->method('addEnum')
            ->with($this->isInstanceOf(Enum::class));
        $mock
            ->expects($this->once())
            ->method('addScalar')
            ->with($this->isInstanceOf(Scalar::class));

        $config = $this->getValidConfig();
        $mock->applyConfig($config);
    }

    public function testFindOrMakeType()
    {
        $schema = $this->buildSchema();
        $schema->addType($a = Type::create('TestType'));
        $this->assertSame($a, $schema->findOrMakeType('TestType'));
        $b = $schema->findOrMakeType('TestType2');
        $this->assertInstanceOf(Type::class, $b);
        $this->assertEquals('TestType2', $b->getName());
    }

    public function testGetTypes()
    {
        $schema = $this->buildSchema()->applyConfig($this->getValidConfig());
        // Three types, two models
        $this->assertCount(3, $schema->getTypes());
        $this->assertCount(2, $schema->getModels());
    }



    public function testExists()
    {
        $schema = $this->buildSchema();
        $this->assertFalse($schema->exists());
        $schema->addEnum(Enum::create('myEnum', ['foo' => 'bar']));
        $this->assertFalse($schema->exists());
        $schema->addType(Type::create('MyType'));
        $this->assertFalse($schema->exists());
        $schema->addMutation(Mutation::create('myMutation'));
        $this->assertFalse($schema->exists());
        $schema->addQuery(Query::create('myQuery'));
        $this->assertTrue($schema->exists());
    }

    public function testSchemaKey()
    {
        $schema = $this->buildSchema('test');
        $this->assertEquals('test', $schema->getSchemaKey());
    }

    public function testSchemaContext()
    {
        $context = $this->createSchemaContext();
        $schema = new Schema('test', $context);
        $this->assertSame($context, $schema->getSchemaContext());
    }

    public function testAddQueriesAndMutations()
    {
        $schema = $this->buildSchema();
        $schema->addQuery(Query::create('foo', ['type' => 'foo']));
        $schema->addMutation(Mutation::create('bar', ['type' => 'foo']));
        $storableSchema = $schema->getStoreableSchema();
        $types = $storableSchema->getTypes();
        $queryType = $types[Schema::QUERY_TYPE] ?? null;
        $this->assertInstanceOf(Type::class, $queryType);
        $mutationType = $types[Schema::MUTATION_TYPE] ?? null;
        $this->assertInstanceOf(Type::class, $mutationType);

        $this->assertInstanceOf(Query::class, $queryType->getFieldByName('foo'));
        $this->assertInstanceOf(Mutation::class, $mutationType->getFieldByName('bar'));
    }

    public function testTypes()
    {
        $schema = $this->buildSchema();
        $dupeType = Type::create('DupeType');
        $schema->addType($type = Type::create('MyType'));
        $mock = $this->getMockBuilder(Type::class)
            ->setConstructorArgs(['DupeType'])
            ->setMethods(['mergeWith'])
            ->getMock();
            $mock->expects($this->once())
                ->method('mergeWith')
                ->with($dupeType);
        $schema->addType($mock);
        $schema->addType($dupeType);

        $this->assertInstanceOf(Type::class, $schema->getType('MyType'));
        $this->assertSame($type, $schema->getType('MyType'));

        $this->assertInstanceOf(Type::class, $schema->getType('DupeType'));

        $this->assertSame($type, $schema->findOrMakeType('MyType'));
        $created = $schema->findOrMakeType('Test');
        $this->assertInstanceOf(Type::class, $schema->getType('Test'));
        $this->assertEquals($created->getName(), $schema->getType('Test')->getName());
    }

    public function testEnums()
    {
        $schema = $this->buildSchema();
        $schema->addEnum($enum = Enum::create('MyEnum', ['foo' => 'bar']));
        $this->assertSame($enum, $schema->getEnum('MyEnum'));
        $schema->addEnum(Enum::create('MyEnum2', ['foo' => 'bar']));
        $this->assertCount(2, $schema->getEnums());
    }

    public function testScalars()
    {
        $schema = $this->buildSchema();
        $schema->addScalar($scalar = Scalar::create('MyScalar', []));
        $this->assertSame($scalar, $schema->getScalar('MyScalar'));
        $schema->addScalar(Scalar::create('MyScalar2', []));
        $this->assertCount(2, $schema->getScalars());
    }

    public function testInterfaces()
    {
        $schema = $this->buildSchema();
        $dupeType = InterfaceType::create('DupeType');
        $schema->addInterface($int = InterfaceType::create('MyType'));
        $mock = $this->getMockBuilder(InterfaceType::class)
            ->setConstructorArgs(['DupeType'])
            ->setMethods(['mergeWith'])
            ->getMock();
        $mock->expects($this->once())
            ->method('mergeWith')
            ->with($dupeType);

        $schema->addInterface($mock);
        $schema->addInterface($dupeType);

        $this->assertInstanceOf(InterfaceType::class, $schema->getInterface('MyType'));
        $this->assertSame($int, $schema->getInterface('MyType'));
        $this->assertCount(2, $schema->getInterfaces());
    }

    public function testUnions()
    {
        $schema = $this->buildSchema();
        $dupeType = UnionType::create('DupeType');
        $schema->addUnion($int = UnionType::create('MyType'));
        $mock = $this->getMockBuilder(UnionType::class)
            ->setConstructorArgs(['DupeType'])
            ->setMethods(['mergeWith'])
            ->getMock();
        $mock->expects($this->once())
            ->method('mergeWith')
            ->with($dupeType);

        $schema->addUnion($mock);
        $schema->addUnion($dupeType);

        $this->assertInstanceOf(UnionType::class, $schema->getUnion('MyType'));
        $this->assertSame($int, $schema->getUnion('MyType'));
        $this->assertCount(2, $schema->getUnions());
    }

    public function testInternalType()
    {
        $this->assertTrue(Schema::isInternalType('String'));
        $this->assertTrue(Schema::isInternalType('Boolean'));
        $this->assertTrue(Schema::isInternalType('Int'));
        $this->assertTrue(Schema::isInternalType('Float'));
        $this->assertFalse(Schema::isInternalType('Object'));
    }

    public function testValidConfig()
    {
        $this->expectException(SchemaBuilderException::class);
        Schema::assertValidConfig(['test']);

        Schema::assertValidConfig(['foo' => 'bar']);

        $this->expectException(SchemaBuilderException::class);
        Schema::assertValidConfig(['foo' => 'bar'], ['qux'], ['foo']);

        $this->expectException(SchemaBuilderException::class);
        Schema::assertValidConfig(['foo' => 'bar'], ['foo'], ['qux']);

        Schema::assertValidConfig(['foo' => 'bar'], ['foo']);

        $this->expectException(SchemaBuilderException::class);
        Schema::assertValidConfig(['foo' => 'bar'], [], ['qux']);

        $this->expectException(SchemaBuilderException::class);
        Schema::assertValidConfig(['foo' => 'bar'], [], ['foo']);

        Schema::assertValidConfig([]);
    }

    public static function noop()
    {
    }

    private function buildSchema(string $key = 'test', SchemaContext $context = null): Schema
    {
        $schema = new Schema($key, $this->createSchemaContext());

        return $schema;
    }

    /**
     * @return array
     */
    private function getValidConfig(): array
    {
        return [
            'types' => [
                'type1' => [
                    'fields' => [
                        'field1' => 'String',
                    ]
                ],
                'type2' => [
                    'fields' => [
                        'field1' => 'Boolean',
                    ]
                ],
                'inputType' => [
                    'fields' => [
                        'field1' => 'String',
                    ],
                    'input' => true,
                ],
            ],
            'interfaces' => [
                'interface1' => [
                    'fields' => [
                        'field1' => 'String',
                    ],
                    'typeResolver' => [static::class, 'noop'],
                ],
            ],
            'unions' => [
                'union1' => [
                    'types' => ['type1', 'type2'],
                    'typeResolver' => [static::class, 'noop'],
                ],
            ],
            'models' => [
                DataObjectFake::class => [
                    'fields' => [
                        'myField' => true,
                    ],
                ],
                FakeSiteTree::class => [
                    'fields' => [
                        'title' => true,
                    ]
                ],
            ],
            'enums' => [
                'myEnum' => [
                    'values' => [
                        'option1' => 'OPTION1',
                        'option2' => 'OPTION2',
                    ]
                ],
            ],
            'scalars' => [
                'myScalar' => [
                    'serialiser' => [static::class, 'noop'],
                ],
            ],
            'queries' => [
                'query1' => 'testtype1',
                'query2' => 'testtype2',
            ],
            'mutations' => [
                'mutation1' => 'testtype3',
            ],
        ];
    }

    /**
     * @return SchemaContext
     */
    private function createSchemaContext(): SchemaContext
    {
        return new SchemaContext([
            'modelCreators' => [ModelCreator::class],
        ]);
    }
}
