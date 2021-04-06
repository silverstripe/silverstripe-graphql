<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceUnionBuilder;
use SilverStripe\GraphQL\Schema\Type\ModelInterfaceType;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\UnionType;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1a;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1b;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A2;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A2a;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B1;

class InheritanceUnionBuilderTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        A::class,
        A1::class,
        A1a::class,
        A1b::class,
        A2::class,
        A2a::class,
        B::class,
        B1::class,
    ];

    public function testCreateUnions()
    {
        $schema = new TestSchema();
        foreach (static::$extra_dataobjects as $class) {
            $schema->addModelbyClassName($class, function (ModelType $model) {
                $model->addAllFields();
            });
        }
        $schema->createStoreableSchema();
        $builder = new InheritanceUnionBuilder($schema);
        $builder->createUnions();

        $union = $schema->getUnion('AInheritanceUnion');
        $this->assertNotNull($union);
        $this->assertTypes(['A', 'A1', 'A2', 'A2a', 'A1a', 'A1b'], $union);

        $union = $schema->getUnion('A1InheritanceUnion');
        $this->assertNotNull($union);
        $this->assertTypes(['A1', 'A1a', 'A1b'], $union);

        $union = $schema->getUnion('A2InheritanceUnion');
        $this->assertNotNull($union);
        $this->assertTypes(['A2', 'A2a'], $union);

        $union = $schema->getUnion('A1aInheritanceUnion');
        $this->assertNull($union);

        $union = $schema->getUnion('A2aInheritanceUnion');
        $this->assertNull($union);

        $schema = new TestSchema();
        foreach (static::$extra_dataobjects as $class) {
            $schema->addModelbyClassName($class, function (ModelType $model) {
                $model->addAllFields();
            });
        }

        // Try removing something from the chain
        $schema->removeModelByClassName(A1::class);
        $schema->createStoreableSchema();
        $builder = new InheritanceUnionBuilder($schema);
        $builder->createUnions();
        ;
        $union = $schema->getUnion('AInheritanceUnion');
        $this->assertNotNull($union);
        $this->assertTypes(['A', 'A2', 'A2a', 'A1a', 'A1b'], $union);

        $union = $schema->getUnion('A1InheritanceUnion');
        $this->assertNull($union);

        $union = $schema->getUnion('A1aInheritanceUnion');
        $this->assertNull($union);

        // Sanity check
        $union = $schema->getUnion('A2aInheritanceUnion');
        $this->assertNull($union);
    }

    public function testApplyUnions()
    {
        $schema = new TestSchema();
        foreach (static::$extra_dataobjects as $class) {
            $schema->addModelbyClassName($class, function (ModelType $model) {
                $model->addAllFields();
                $model->addOperation('read');
            });
        }
        $schema->getModelByClassName(A::class)->addField('allTheB', '[B]');
        $a = $schema->getModel('A');
        $interface = new ModelInterfaceType($a->getModel(), 'AInterface');
        $modelQuery = clone $a->getFieldByName('allTheB');
        $interface->addField('allTheB', $modelQuery);
        $schema->addInterface($interface);
        $a->addInterface('AInterface');

        $schema->createStoreableSchema();
        $builder = new InheritanceUnionBuilder($schema);
        $builder->createUnions();
        $builder->applyUnions();

        $query = $schema->getQueryType()->getFieldByName('readAs');
        $this->assertNotNull($query);
        $this->assertEquals('AInheritanceUnion', $query->getNamedType());

        $query = $schema->getQueryType()->getFieldByName('readA1s');
        $this->assertNotNull($query);
        $this->assertEquals('A1InheritanceUnion', $query->getNamedType());

        $type = $schema->getModelByClassName(A::class);
        $nestedQuery = $type->getFieldByName('allTheB');
        $this->assertNotNull($nestedQuery);
        $this->assertEquals('BInheritanceUnion', $nestedQuery->getNamedType());


        $interface = $schema->getInterface('AInterface');
        $this->assertEquals('BInheritanceUnion', $interface->getFieldByName('allTheB')->getNamedType());
    }

    public function testUnionName()
    {
        $schema = new TestSchema();
        $this->assertEquals('FooInheritanceUnion', InheritanceUnionBuilder::unionName('Foo', $schema->getConfig()));
        $schema->applyConfig([
            'config' => [
                'inheritanceUnionBuilder' => [
                    'name_formatter' => 'strrev',
                ],
            ],
        ]);
        $schema->createStoreableSchema();
        $this->assertEquals('ooF', InheritanceUnionBuilder::unionName('Foo', $schema->getConfig()));
    }

    /**
     * @param array $types
     * @param UnionType $union
     */
    private function assertTypes(array $types, UnionType $union)
    {
        $expected = array_map('strtolower', $types);
        $compare = array_map('strtolower', $union->getTypes());

        $this->assertEmpty(array_diff($expected, $compare));
        $this->assertEmpty(array_diff($compare, $expected));
    }
}
