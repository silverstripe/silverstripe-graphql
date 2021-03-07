<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin;


use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\ModelCreator;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\Inheritance;
use SilverStripe\GraphQL\Schema\DataObject\ReadCreator;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaComponent;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1a;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1b;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A2;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A2a;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B1;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B1a;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B1b;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B2;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\C;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\C1;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\C2;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\C2a;

class InheritanceTest extends SapphireTest
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
        B1a::class,
        B1b::class,
        B2::class,
        C::class,
        C1::class,
        C2::class,
        C2a::class,
    ];

    public function testFillAncestry()
    {
        $schema = $this->createSchema();

        // A1 and A are not in this schema, because only the A1a descendant
        // has been exposed for reading.
        $schema->applyConfig([
            'models' => [
                A1a::class => [
                    'fields' => [
                        'A1aField' => true,
                        'AField' => true,
                    ],
                    'operations' => ['read' => true],
                ],
            ],
        ]);
        $schema->createStoreableSchema();

        Inheritance::updateSchema($schema);
        $a1a = $schema->getModelByClassName(A1a::class);
        $this->assertNotNull($a1a);
        $this->assertFields(['A1aField', 'AField', 'id'], $a1a);

        $a1 = $schema->getModelByClassName(A1::class);
        $this->assertNull($a1);
        $a = $schema->getModelByClassName(A::class);
        $this->assertNull($a);

        $schema = $this->createSchema();

        // Now A and A1 will be exposed because A is the base model, having
        // a read operation
        $schema->applyConfig([
            'models' => [
                A::class => [
                    'operations' => ['read' => true],
                ],
                A1a::class => [
                    'fields' => [
                        'A1aField' => true,
                        'AField' => true,
                    ],
                    'operations' => ['read' => true],
                ],
            ],
        ]);
        $schema->createStoreableSchema();

        Inheritance::updateSchema($schema);
        $a1a = $schema->getModelByClassName(A1a::class);
        $this->assertNotNull($a1a);
        $this->assertFields(['A1aField', 'AField', 'id'], $a1a);

        $a1 = $schema->getModelByClassName(A1::class);
        $this->assertNotNull($a1);
        $this->assertFields(['AField', 'id'], $a1);
        $a = $schema->getModelByClassName(A::class);
        $this->assertNotNull($a);
        $this->assertFields(['AField', 'id'], $a1);
    }

    public function testFillDescendants()
    {
        $schema = $this->createSchema();
        $schema->applyConfig([
            'models' => [
                A::class => [
                    'fields' => [
                        'AField' => true,
                    ],
                    'operations' => ['read' => true],
                ],
                A1::class => [
                    'fields' => [
                        'A1Field' => true,
                    ],
                ],
                B::class => [
                    'fields' => [
                        'BField' => true,
                    ],
                    'operations' => ['read' => true],
                ],
                B1a::class => [
                    'fields' => [
                        'B1aField' => true,
                        'B1Field' => true,
                    ],
                ],
                C::class => [
                    'fields' => [
                        'CField' => true,
                    ],
                    'operations' => ['read' => true],
                ],
                C2a::class => [
                    'fields' => [
                        'C2aField' => true,
                        'C2Field' => true,
                    ],
                ],

            ],
        ]);
        $schema->createStoreableSchema();

        Inheritance::updateSchema($schema);
        $a = $schema->getModelByClassName(A::class);
        $this->assertNotNull($a);
        $this->assertFields(['AField', 'id'], $a);
        $a1 = $schema->getModelByClassName(A1::class);
        $this->assertNotNull($a1);
        $this->assertFields(['A1Field', 'AField', 'id'], $a1);

        // This descendant shouldn't be added implicitly, because it was never
        // assigned any fields or operations.
        $this->assertNull($schema->getModelByClassName(A1a::class));

        $b = $schema->getModelByClassName(B::class);
        $this->assertNotNull($b);
        $this->assertFields(['BField', 'id'], $b);

        // B1 should have been implicitly added, because B1a was
        $b1 = $schema->getModelByClassName(B1::class);
        $this->assertNotNull($b1);
        // B1 has no native fields, but its descendant is, so all
        // it has is its inherited fields from B
        $this->assertFields(['BField', 'id'], $b1);

        $b1a = $schema->getModelByClassName(B1a::class);
        $this->assertNotNull($b1a);
        // Its native fields were added by its descendant B1a
        $this->assertFields(['B1aField', 'B1Field', 'BField', 'id'], $b1a);

        $c = $schema->getModelByClassName(C::class);
        $this->assertNotNull($c);
        $this->assertFields(['CField', 'id'], $c);

        // Sanity check -- no siblings.
        $this->assertNull($schema->getModelByClassName(C1::class));

        // B1 should have been implicitly added, because B1a was
        $c2 = $schema->getModelByClassName(C2::class);
        $this->assertNotNull($c2);
        // C2 gets its native field added by its descendant C2a
        $this->assertFields(['C2Field', 'CField', 'id'], $c2);

        $c2a = $schema->getModelByClassName(C2a::class);
        $this->assertNotNull($c2a);
        $this->assertFields(['C2aField', 'C2Field', 'CField', 'id'], $c2a);
    }

    public function testCreatesInterfaces()
    {
        $schema = $this->createSchema();

        // Leave out a couple classes to test implicit adding
        $classes = array_filter(static::$extra_dataobjects, function ($class) {
            return !in_array($class, [A2a::class, C2::class]);
        });
        foreach ($classes as $class) {
            $schema->addModelbyClassName($class, function (ModelType $type) {
                $type->addAllFields();
                $type->addOperation('read');
            });
        }
        $schema->createStoreableSchema();
        Inheritance::updateSchema($schema);

        $a1a = $schema->getModelByClassName(A1a::class);
        $this->assertNotNull($a1a);
        // A1a is a leaf. No interface.
        $this->assertNull($schema->getInterface(Inheritance::modelToInterface($a1a)));
        $this->assertInterfaces(['DataObjectInterface', 'A1Interface', 'AInterface'], $a1a);

        $a1 = $schema->getModelByClassName(A1::class);
        $this->assertNotNull($a1);
        $interface = $schema->getInterface(Inheritance::modelToInterface($a1));
        $this->assertNotNull($interface);
        $this->assertFields(['A1Field'], $interface);
        $this->assertInterfaces(['DataObjectInterface', 'A1Interface', 'AInterface'], $a1);

        $a = $schema->getModelByClassName(A::class);
        $this->assertNotNull($a);
        $interface = $schema->getInterface(Inheritance::modelToInterface($a));
        $this->assertNotNull($interface);
        $this->assertFields(['AField'], $interface);
        $this->assertInterfaces(['DataObjectInterface', 'AInterface'], $a);

        $this->assertNull($schema->getModelByClassName(A2a::class));
        $this->assertNull($schema->getInterface('A2aInterface'));

        $a2 = $schema->getModelByClassName(A2::class);
        $this->assertNotNull($a2);
        $interface = $schema->getInterface(Inheritance::modelToInterface($a2));
        // There is no interface for A2 because it's a leaf. A2a was never added.
        $this->assertNull($interface);
        $this->assertInterfaces(['DataObjectInterface', 'AInterface'], $a2);

        $b1a = $schema->getModelByClassName(B1a::class);
        $this->assertNotNull($b1a);
        $interface = $schema->getInterface(Inheritance::modelToInterface($b1a));
        $this->assertNull($interface);
        $this->assertInterfaces(['DataObjectInterface', 'BInterface'], $b1a);

        $b1 = $schema->getModelByClassName(B1::class);
        $this->assertNotNull($b1);
        $interface = $schema->getInterface(Inheritance::modelToInterface($b1));
        $this->assertNull($interface);

        $b = $schema->getModelByClassName(B::class);
        $this->assertNotNull($b);
        $interface = $schema->getInterface(Inheritance::modelToInterface($b));
        $this->assertNotNull($interface);
        $this->assertFields(['BField'], $interface);
        $this->assertInterfaces(['DataObjectInterface', 'BInterface'], $b);

        $b1b = $schema->getModelByClassName(B1b::class);
        $this->assertNotNull($b1b);
        $interface = $schema->getInterface(Inheritance::modelToInterface($b1b));
        $this->assertNull($interface);
        $this->assertInterfaces(['DataObjectInterface', 'BInterface'], $b1b);

        $c1 = $schema->getModelByClassName(C1::class);
        $this->assertNotNull($c1);
        $interface = $schema->getInterface(Inheritance::modelToInterface($c1));
        $this->assertNull($interface);
        $this->assertInterfaces(['DataObjectInterface', 'CInterface'], $c1);

        $c2a = $schema->getModelByClassName(C2a::class);
        $this->assertNotNull($c2a);
        $interface = $schema->getInterface(Inheritance::modelToInterface($c2a));
        $this->assertNull($interface);
        $this->assertInterfaces(['DataObjectInterface', 'C2Interface', 'CInterface'], $c2a);

        $c2 = $schema->getModelByClassName(C2::class);
        $this->assertNotNull($c2);
        $interface = $schema->getInterface(Inheritance::modelToInterface($c2));
        $this->assertFields(['C2Field'], $interface);
        $this->assertInterfaces(['DataObjectInterface', 'C2Interface', 'CInterface'], $c2);
    }

    public function testBaseInterface()
    {
        $schema = $this->createSchema();

        foreach (static::$extra_dataobjects as $class) {
            $schema->addModelbyClassName($class, function (ModelType $type) {
                $type->addAllFields();
                $type->addOperation('read');
            });
        }
        $schema->createStoreableSchema();
        Inheritance::updateSchema($schema);

        $base = $schema->getInterface('DataObjectInterface');
        $this->assertNotNull($base);
        $this->assertFields(['lastEdited', 'created', 'id'], $base);

        foreach ($schema->getModels() as $model) {
            $this->assertContains('DataObjectInterface', $model->getInterfaces());
        }
    }

    public function testUnions()
    {
        $schema = $this->createSchema();

        // Leave out a couple classes to test implicit adding
        $classes = array_filter(static::$extra_dataobjects, function ($class) {
            return !in_array($class, [A2a::class, C2::class]);
        });
        foreach ($classes as $class) {
            $schema->addModelbyClassName($class, function (ModelType $type) {
                $type->addAllFields();
                $type->addOperation('read');
            });
        }
        $schema->createStoreableSchema();
        Inheritance::updateSchema($schema);

        $union = $schema->getUnion('AInheritanceUnion');
        $this->assertNotNull($union);
        $types = $union->getTypes();
        $this->assertCount(5, $types);
        $this->assertContains('A', $types);
        $this->assertContains('A1', $types);
        $this->assertContains('A2', $types);
        $this->assertContains('A1a', $types);
        $this->assertContains('A1b', $types);
        $this->assertNotContains('A2a', $types);
        $this->assertNotContains('B', $types);

        $union = $schema->getUnion('A1InheritanceUnion');
        $this->assertNotNull($union);
        $types = $union->getTypes();
        $this->assertCount(3, $types);
        $this->assertContains('A1', $types);
        $this->assertContains('A1a', $types);
        $this->assertContains('A1b', $types);
        $this->assertNotContains('A2a', $types);

        $union = $schema->getUnion('A2InheritanceUnion');
        $this->assertNull($union);

        $union = $schema->getUnion('BInheritanceUnion');
        $this->assertNotNull($union);
        $types = $union->getTypes();
        $this->assertCount(5, $types);
        $this->assertContains('B', $types);
        $this->assertContains('B1', $types);
        $this->assertContains('B2', $types);
        $this->assertContains('B1a', $types);
        $this->assertContains('B1b', $types);
        $this->assertNotContains('A', $types);

        // B1 has no fields
        $union = $schema->getUnion('B1InheritanceUnion');
        $this->assertNull($union);

        // B2 has no descendants
        $union = $schema->getUnion('B2InheritanceUnion');
        $this->assertNull($union);

        $union = $schema->getUnion('CInheritanceUnion');
        $this->assertNotNull($union);
        $types = $union->getTypes();
        $this->assertCount(4, $types);
        $this->assertContains('C', $types);
        $this->assertContains('C1', $types);
        $this->assertContains('C2', $types);
        $this->assertContains('C2a', $types);

        $union = $schema->getUnion('C2InheritanceUnion');
        $this->assertNotNull($union);
        $types = $union->getTypes();
        $this->assertCount(2, $types);
        $this->assertContains('C2', $types);
        $this->assertContains('C2a', $types);

    }

    /**
     * @param array $fields
     * @param Type $type
     */
    private function assertFields(array $fields, Type $type)
    {
        $compare = array_map('strtolower', array_keys($type->getFields()));
        // Alpha sort with all these A1 A1a type fields isn't intuitive, so
        // strlen FTW
        usort($compare, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        $this->assertEquals(array_map('strtolower', $fields), $compare);
    }

    /**
     * @param array $expected
     * @param Type $type
     */
    private function assertInterfaces(array $expected, Type $type)
    {
        $interfaces = $type->getInterfaces();
        $this->assertCount(count($expected), $interfaces);
        usort($interfaces, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        $this->assertEquals($expected, $interfaces);
    }

    /**
     * @return Schema
     */
    private function createSchema(): Schema
    {
        $schema = Schema::create('test', new SchemaConfig([
            'modelCreators' => [ ModelCreator::class ],
            'modelConfig' => [
                'DataObject' => [
                    'operations' => [
                        'read' => [
                            'class' => ReadCreator::class,
                        ],
                    ],
                ],
            ]
        ]));
        return $schema;
    }
}
