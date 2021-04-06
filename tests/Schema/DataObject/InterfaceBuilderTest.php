<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject;


use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\InterfaceBuilder;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1a;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1b;

class InterfaceBuilderTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        A::class,
        A1::class,
        A1a::class,
        A1b::class,
    ];

    public function testCreateInterfaces()
    {
        $schema = new TestSchema();
        foreach (static::$extra_dataobjects as $class) {
            $schema->addModelbyClassName($class, function (ModelType $model) {
                $model->addAllFields();
            });
        }
        $schema->createStoreableSchema();
        $builder = new InterfaceBuilder($schema);
        $builder->createInterfaces($schema->getModelByClassName(A::class));
        $interface = $schema->getInterface('AInterface');
        $this->assertNotNull($interface);
        $this->assertFields(['LastEdited', 'Created', 'AField', 'ID'], $interface);

        $interface = $schema->getInterface('A1Interface');
        $this->assertNotNull($interface);
        $this->assertFields(['LastEdited', 'Created', 'AField', 'A1Field', 'ID'], $interface);

        $interface = $schema->getInterface('A1aInterface');
        $this->assertNotNull($interface);
        $this->assertFields(['LastEdited', 'Created', 'A1Field', 'AField', 'A1aField',  'ID'], $interface);

        $schema = new TestSchema();
        $schema->applyConfig([
            'models' => [
                A::class => [
                    'fields' => ['AField' => true],
                ],
                A1::class => [
                    'fields' => ['AField' => true],
                ],
                A1a::class => [
                    'fields' => [
                        'A1aField' => true,
                        'AField' => true,
                        'A1Field' => true,
                    ],
                ],

            ]
        ]);
        $schema->createStoreableSchema();

        $builder = new InterfaceBuilder($schema);
        $builder->createInterfaces($schema->getModelByClassName(A::class));
        $interface = $schema->getInterface('AInterface');
        $this->assertNotNull($interface);
        $this->assertFields(['AField', 'ID'], $interface);

        // A1 never exposed any of its own fields, so it's just a copy of A
        $interface = $schema->getInterface('A1Interface');
        $this->assertNotNull($interface);
        $this->assertFields(['AField', 'ID'], $interface);

        $interface = $schema->getInterface('A1aInterface');
        $this->assertNotNull($interface);
        $this->assertFields(['AField', 'A1aField', 'A1Field', 'ID'], $interface);

        $model = $schema->getModelByClassName(A::class);
        $this->assertNotNull($model);

        $this->assertInterfaces(['AInterface'], $model);

        $model = $schema->getModelByClassName(A1::class);
        $this->assertNotNull($model);

        $this->assertInterfaces(['A1Interface', 'AInterface'], $model);

        $model = $schema->getModelByClassName(A1a::class);
        $this->assertNotNull($model);

        $this->assertInterfaces(['A1Interface', 'AInterface', 'A1aInterface'], $model);

    }

    public function testBaseInterface()
    {
        $schema = new TestSchema();
        foreach (static::$extra_dataobjects as $class) {
            $schema->addModelbyClassName($class, function (ModelType $model) {
                $model->addAllFields();
            });
        }
        $schema->createStoreableSchema();
        $builder = new InterfaceBuilder($schema);
        $builder->applyBaseInterface();
        $interface = $schema->getInterface(InterfaceBuilder::BASE_INTERFACE_NAME);
        $this->assertNotNull($interface);
        $this->assertFields(['ID'], $interface);

        $schema = new TestSchema();
        foreach (static::$extra_dataobjects as $class) {
            $schema->addModelbyClassName($class, function (ModelType $model) {
                $model->addAllFields();
            });
        }
        $schema->applyConfig([
            'config' => [
                'modelConfig' => [
                    'DataObject' => [
                        'base_fields' => [
                            'id' => 'ID',
                            'lastEdited' => 'String',
                            'className' => 'String',
                        ]
                    ]
                ]
            ]
        ]);
        $schema->createStoreableSchema();
        $builder = new InterfaceBuilder($schema);
        $builder->applyBaseInterface();
        $interface = $schema->getInterface(InterfaceBuilder::BASE_INTERFACE_NAME);
        $this->assertNotNull($interface);
        $this->assertFields(['ID', 'LastEdited', 'ClassName'], $interface);
    }

    public function testInterfaceName()
    {
        $schema = new TestSchema();
        $this->assertEquals('FooInterface', InterfaceBuilder::interfaceName('Foo', $schema->getConfig()));
        $schema->applyConfig([
            'config' => [
                'interfaceBuilder' => [
                    'name_formatter' => 'strrev',
                ],
            ],
        ]);
        $schema->createStoreableSchema();
        $this->assertEquals('ooF', InterfaceBuilder::interfaceName('Foo', $schema->getConfig()));
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

    /**
     * @param array $fields
     * @param Type $type
     */
    private function assertInterfaces(array $fields, Type $type)
    {
        $expected = array_map('strtolower', $fields);
        $compare = array_map('strtolower', $type->getInterfaces());

        $this->assertEmpty(array_diff($expected, $compare));
        $this->assertEmpty(array_diff($compare, $expected));
    }

}
