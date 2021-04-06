<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject;


use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceBuilder;
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

class InheritanceBuilderTest extends SapphireTest
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

    public function testBaseModel()
    {
        $schema = new TestSchema();

        $schema->applyConfig([
            'models' => [
                A1a::class => [
                    'fields' => [
                        'A1aField' => true,
                        'AField' => true,
                    ],
                ],
            ],
        ]);
        $schema->createStoreableSchema();

        $builder = new InheritanceBuilder($schema);
        $this->assertTrue($builder->isBaseModel(A1a::class));
        $this->assertFalse($builder->isBaseModel(A1::class));
        $this->assertFalse($builder->isBaseModel(A::class));

        $schema->applyConfig([
            'models' => [
                A1::class => [
                    'fields' => [
                        'A1Field' => true,
                    ],
                ],
                A1a::class => [
                    'fields' => [
                        'A1aField' => true,
                        'AField' => true,
                    ],
                ],
            ],
        ]);
        $schema->createStoreableSchema();
        $this->assertTrue($builder->isBaseModel(A1::class));
        $this->assertFalse($builder->isBaseModel(A1a::class));
        $this->assertFalse($builder->isBaseModel(A::class));

        $schema->applyConfig([
            'models' => [
                A::class => [
                    'fields' => [
                        'AField' => true,
                    ],
                ],
                A1::class => [
                    'fields' => [
                        'A1Field' => true,
                    ],
                ],
                A1a::class => [
                    'fields' => [
                        'A1aField' => true,
                        'AField' => true,
                    ],
                ],
            ],
        ]);
        $schema->createStoreableSchema();
        $this->assertTrue($builder->isBaseModel(A::class));
        $this->assertFalse($builder->isBaseModel(A1::class));
        $this->assertFalse($builder->isBaseModel(Aa::class));

    }

    public function testLeafModel()
    {
        $schema = new TestSchema();

        $schema->applyConfig([
            'models' => [
                A1a::class => [
                    'fields' => [
                        'A1aField' => true,
                        'AField' => true,
                    ],
                ],
            ],
        ]);
        $schema->createStoreableSchema();

        $builder = new InheritanceBuilder($schema);
        $this->assertTrue($builder->isLeafModel(A1a::class));
        $this->assertFalse($builder->isLeafModel(A1::class));
        $this->assertFalse($builder->isLeafModel(A::class));

        $schema->applyConfig([
            'models' => [
                A1::class => [
                    'fields' => [
                        'A1Field' => true,
                    ],
                ],
                A1a::class => [
                    'fields' => [
                        'A1aField' => true,
                        'AField' => true,
                    ],
                ],
            ],
        ]);
        $schema->createStoreableSchema();
        $this->assertTrue($builder->isLeafModel(A1a::class));
        $this->assertFalse($builder->isLeafModel(A1::class));
        $this->assertFalse($builder->isLeafModel(A::class));

        $schema->applyConfig([
            'models' => [
                A::class => [
                    'fields' => [
                        'AField' => true,
                    ],
                ],
                A1::class => [
                    'fields' => [
                        'A1Field' => true,
                    ],
                ],
                A1a::class => [
                    'fields' => [
                        'A1aField' => true,
                        'AField' => true,
                    ],
                ],
            ],
        ]);
        $schema->createStoreableSchema();
        $this->assertTrue($builder->isLeafModel(A1a::class));
        $this->assertFalse($builder->isLeafModel(A1::class));
        $this->assertFalse($builder->isLeafModel(A::class));

    }

    public function testFillAncestry()
    {
        $schema = new TestSchema();

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

        $builder = new InheritanceBuilder($schema);
        $builder->fillAncestry($schema->getModelByClassName(A1a::class));

        $a1a = $schema->getModelByClassName(A1a::class);
        $this->assertNotNull($a1a);
        $this->assertFields(['A1aField', 'AField', 'id'], $a1a);

        $a1 = $schema->getModelByClassName(A1::class);
        $this->assertNotNull($a1);
        $this->assertFields(['AField', 'id'], $a1);

        $a = $schema->getModelByClassName(A::class);
        $this->assertNotNull($a);
        $this->assertFields(['AField', 'id'], $a);

    }

    public function testFillDescendants()
    {
        $schema = new TestSchema();
        $schema->applyConfig([
            'models' => [
                A::class => [
                    'fields' => [
                        'AField' => true,
                    ],
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
                ],
                B1a::class => [
                    'fields' => [
                        'B1aField' => true,
                    ],
                ],
                C::class => [
                    'fields' => [
                        'CField' => true,
                    ],
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

        $builder = new InheritanceBuilder($schema);
        $builder->fillDescendants($schema->getModelByClassName(A::class));

        $a = $schema->getModelByClassName(A::class);
        $this->assertNotNull($a);
        $this->assertFields(['AField', 'id'], $a);
        $a1 = $schema->getModelByClassName(A1::class);
        $this->assertNotNull($a1);
        $this->assertFields(['A1Field', 'AField', 'id'], $a1);

        // This descendant wasn't explicitly exposed.
        $a1a = $schema->getModelByClassName(A1a::class);
        $this->assertNull($a1a);


        // B
        $builder->fillDescendants($schema->getModelByClassName(B::class));

        $b = $schema->getModelByClassName(B::class);
        $this->assertNotNull($b);
        $this->assertFields(['BField', 'id'], $b);

        // This descendant wasn't explicitly exposed.
        $b1 = $schema->getModelByClassName(B1::class);
        $this->assertNull($b1);

        // But this one was. In practice, B1 would be exposed by fillAncestry()
        $b1a = $schema->getModelByClassName(B1a::class);
        $this->assertNotNull($b1a);
        // Only has its native fields. fillAncestry() would take care of the others in practice.
        $this->assertFields(['B1aField', 'id'], $b1a);

        // C
        $builder->fillDescendants($schema->getModelByClassName(C::class));

        $c = $schema->getModelByClassName(C::class);
        $this->assertNotNull($c);
        $this->assertFields(['CField', 'id'], $c);

        // Not explicitly exposed
        $this->assertNull($schema->getModelByClassName(C1::class));

        // Not explicitly exposed, but would be in practice by fillAncestry(), due to C2a
        $c2 = $schema->getModelByClassName(C2::class);
        $this->assertNull($c2);

        $c2a = $schema->getModelByClassName(C2a::class);
        $this->assertNotNull($c2a);
        // Only has what was explicitly added. fillAncestry() would take care of the others in practice.
        $this->assertFields(['C2aField', 'C2Field', 'id'], $c2a);
    }

    /**
     * @param array $fields
     * @param Type $type
     */
    private function assertFields(array $fields, Type $type)
    {
        $expected = array_map('strtolower', $fields);
        $compare = array_map('strtolower', array_keys($type->getFields()));

        $this->assertEmpty(array_diff($expected, $compare));
        $this->assertEmpty(array_diff($compare, $expected));
    }

}
