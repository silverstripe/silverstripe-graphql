<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding\Scaffolders\CRUD;

use Exception;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\StringType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Create;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeCRUDExtension;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\Security\Member;

class CreateTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class,
        RestrictedDataObjectFake::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        StaticSchema::inst()->setTypeNames([]);
        // Make sure we're only testing the native features
        foreach (Create::get_extensions() as $className) {
            Create::remove_extension($className);
        }
    }

    protected function tearDown()
    {
        StaticSchema::inst()->setTypeNames([]);
        parent::tearDown();
    }

    public function getExtensionDataProvider()
    {
        return [
            [false],
            [true],
        ];
    }

    /**
     * @dataProvider getExtensionDataProvider
     *
     * @param bool $shouldExtend
     */
    public function testCreateOperationResolver($shouldExtend)
    {
        if ($shouldExtend) {
            Create::add_extension(FakeCRUDExtension::class);
        }
        StaticSchema::inst()->setTypeNames([
            DataObjectFake::class => 'FakeObject',
        ]);
        $create = new Create(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'FakeObject']), 'FakeObject');
        $create->addToManager($manager);
        $scaffold = $create->scaffold($manager);

        $newRecord = $scaffold['resolve'](
            null,
            [
                'Input' => ['MyField' => '__testing__'],
            ],
            [
                'currentUser' => Member::create(),
            ],
            new ResolveInfo([])
        );

        if ($shouldExtend) {
            $this->assertNull($newRecord);
        } else {
            $this->assertGreaterThan(0, $newRecord->ID);
            $this->assertEquals('__testing__', $newRecord->MyField);
        }
    }

    public function testCreateOperationInputType()
    {
        StaticSchema::inst()->setTypeNames([
            DataObjectFake::class => 'FakeObject',
        ]);
        $create = new Create(DataObjectFake::class);
        $create->addArg('MyField', 'String');
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'FakeObject']), 'FakeObject');
        $create->addToManager($manager);
        $scaffold = $create->scaffold($manager);

        // Test args
        $args = $scaffold['args'];
        $this->assertEquals(['Input', 'MyField'], array_keys($args));

        // Custom field
        $this->assertArrayHasKey('MyField', $args);
        $this->assertInstanceOf(StringType::class, $args['MyField']['type']);

        /** @var NonNull $inputType */
        $inputType = $args['Input']['type'];
        $this->assertInstanceOf(NonNull::class, $inputType);
        /** @var InputObjectType $inputTypeWrapped */
        $inputTypeWrapped = $inputType->getWrappedType();
        $this->assertInstanceOf(InputObjectType::class, $inputTypeWrapped);
        $this->assertEquals('FakeObjectCreateInputType', $inputTypeWrapped->toString());
        ;

        // Check fields
        $config = $inputTypeWrapped->config;
        $fieldMap = [];
        foreach ($config['fields']() as $name => $fieldData) {
            $fieldMap[$name] = $fieldData['type'];
        }
        $this->assertArrayHasKey('Created', $fieldMap, 'Includes fixed_fields');
        $this->assertArrayHasKey('MyField', $fieldMap);
        $this->assertArrayHasKey('MyInt', $fieldMap);
        $this->assertArrayNotHasKey('ID', $fieldMap);
        $this->assertInstanceOf(StringType::class, $fieldMap['MyField']);
        $this->assertInstanceOf(IntType::class, $fieldMap['MyInt']);
    }

    public function testCreateOperationPermissionCheck()
    {
        StaticSchema::inst()->setTypeNames([
            RestrictedDataObjectFake::class => 'RestrictedFakeObject',
        ]);
        $create = new Create(RestrictedDataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'RestrictedFakeObject']), 'RestrictedFakeObject');
        $create->addToManager($manager);
        $scaffold = $create->scaffold($manager);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/Cannot create/');

        $scaffold['resolve'](
            null,
            [],
            ['currentUser' => Member::create()],
            new ResolveInfo([])
        );
    }
}
