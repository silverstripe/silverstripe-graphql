<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Factory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceBuilder;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceUnionBuilder;
use SilverStripe\GraphQL\Schema\DataObject\InterfaceBuilder;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\Inheritance;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;
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
use SilverStripe\GraphQL\Tests\Schema\DataObject\FakeInheritanceBuilder;
use SilverStripe\GraphQL\Tests\Schema\DataObject\FakeInheritanceUnionBuilder;
use SilverStripe\GraphQL\Tests\Schema\DataObject\FakeInterfaceBuilder;
use SilverStripe\GraphQL\Tests\Schema\DataObject\TestSchema;
use SilverStripe\ORM\DataObject;

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


    /**
     * @param false $unions
     * @throws \ReflectionException
     * @throws SchemaBuilderException
     * @dataProvider provideUnionOption
     */
    public function testInheritance($unions = false)
    {
        $schema = new TestSchema();
        foreach (static::$extra_dataobjects as $class) {
            $schema->addModelbyClassName($class, function (ModelType $type) {
                $type->addAllFields();
            });
        }
        $schema->createStoreableSchema();

        $baseClasses = array_filter(static::$extra_dataobjects, function ($class) {
            return DataObject::singleton($class)->baseClass() === $class;
        });
        $leafClasses = array_filter(static::$extra_dataobjects, function ($class) {
            return empty(ClassInfo::subclassesFor($class, false));
        });
        $leafModels = array_map(function ($class) use ($schema) {
            return $schema->getConfig()->getTypeNameForClass($class);
        }, $leafClasses ?? []);

        $baseModels = array_map(function ($class) use ($schema) {
            return $schema->getConfig()->getTypeNameForClass($class);
        }, $baseClasses ?? []);

        FakeInheritanceUnionBuilder::reset();
        FakeInterfaceBuilder::reset();

        Injector::inst()->load([
            InheritanceBuilder::class => [
                'class' => FakeInheritanceBuilder::class,
            ],
            InterfaceBuilder::class => [
                'class' => FakeInterfaceBuilder::class,
            ],
            InheritanceUnionBuilder::class => [
                'class' => FakeInheritanceUnionBuilder::class,
            ],
        ]);
        $schema->getConfig()->set('useUnionQueries', $unions);

        Inheritance::updateSchema($schema);
        $this->assertTrue(FakeInterfaceBuilder::$baseCalled);

        $inheritance = new Inheritance();
        foreach (static::$extra_dataobjects as $class) {
            $inheritance->apply(
                $schema->getModelByClassName($class),
                $schema,
                ['useUnionQueries' => $unions]
            );
        }


        if ($unions) {
            $this->assertCalls(
                $baseModels,
                FakeInheritanceUnionBuilder::$applyCalls
            );
            $this->assertCalls(
                $baseModels,
                FakeInheritanceUnionBuilder::$createCalls
            );
            $this->assertEmpty(FakeInterfaceBuilder::$applyCalls);
        } else {
            $this->assertEmpty(FakeInheritanceUnionBuilder::$createCalls);
            $this->assertEmpty(FakeInheritanceUnionBuilder::$applyCalls);
            $this->assertCalls(
                $baseModels,
                FakeInterfaceBuilder::$applyCalls
            );
            $this->assertCalls(
                $baseModels,
                FakeInterfaceBuilder::$createCalls
            );
        }

        $this->assertCalls(
            $leafModels,
            FakeInheritanceBuilder::$ancestryCalls
        );
        $this->assertCalls(
            $baseModels,
            FakeInheritanceBuilder::$descendantCalls
        );

        $this->assertCalls(
            $baseModels,
            FakeInterfaceBuilder::$createCalls
        );
    }

    /**
     * @param array $expected
     * @param array $actual
     */
    private function assertCalls(array $expected, array $actual)
    {
        $expected = array_map('strtolower', $expected ?? []);
        $compare = array_map('strtolower', array_keys($actual ?? []));

        $this->assertEmpty(array_diff($expected ?? [], $compare), 'Expected calls exceed the actual calls');
        $this->assertEmpty(array_diff($compare ?? [], $expected), 'Actual calls exceed the expected calls');
    }

    public function provideUnionOption()
    {
        return [
            [true],
            [false],
        ];
    }
}
