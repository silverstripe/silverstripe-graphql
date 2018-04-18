<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding\Scaffolders\CRUD;

use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeCRUDExtension;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Update;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Security\Member;
use Exception;

class UpdateTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class,
        RestrictedDataObjectFake::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        // Make sure we're only testing the native features
        foreach (Update::get_extensions() as $className) {
            Update::remove_extension($className);
        }
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
    public function testUpdateOperationResolver($shouldExtend)
    {
        if ($shouldExtend) {
            Update::add_extension(FakeCRUDExtension::class);
        }
        $update = new Update(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $update->addToManager($manager);
        $scaffold = $update->scaffold($manager);

        $record = DataObjectFake::create([
            'MyField' => 'old',
        ]);
        $ID = $record->write();

        $scaffold['resolve'](
            $record,
            [
                'Input' => [
                    'ID' => $ID,
                    'MyField' => 'new'
                ],
            ],
            [
                'currentUser' => Member::create(),
            ],
            new ResolveInfo([])
        );

        /** @var DataObjectFake $updatedRecord */
        $updatedRecord = DataObjectFake::get()->byID($ID);
        if ($shouldExtend) {
            $this->assertEquals('old', $updatedRecord->MyField);
        } else {
            $this->assertEquals('new', $updatedRecord->MyField);
        }
    }

    public function testUpdateOperationInputType()
    {
        $update = new Update(DataObjectFake::class);
        $update->addArg('MyField', 'String');
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $update->addToManager($manager);
        $scaffold = $update->scaffold($manager);

        // Test args
        $args = $scaffold['args'];
        $this->assertEquals(['Input', 'MyField'], array_keys($args));

        /** @var NonNull $inputType */
        $inputType = $args['Input']['type'];
        $this->assertInstanceOf(NonNull::class, $inputType);
        /** @var InputObjectType $inputTypeWrapped */
        $inputTypeWrapped = $inputType->getWrappedType();
        $this->assertInstanceOf(InputObjectType::class, $inputTypeWrapped);
        $this->assertEquals('SilverStripeDataObjectFakeUpdateInputType', $inputTypeWrapped->toString());

        // Custom field
        $this->assertInstanceOf(StringType::class, $args['MyField']['type']);

        // Test fields
        $config = $inputTypeWrapped->config;
        $fieldMap = [];
        foreach ($config['fields']() as $name => $fieldData) {
            $fieldMap[$name] = $fieldData['type'];
        }
        $this->assertArrayHasKey('Created', $fieldMap, 'Includes fixed_fields');
        $this->assertArrayHasKey('MyField', $fieldMap);
        $this->assertArrayHasKey('MyInt', $fieldMap);
        $this->assertArrayHasKey('ID', $fieldMap);
        $this->assertInstanceOf(NonNull::class, $fieldMap['ID']);
        $this->assertInstanceOf(IDType::class, $fieldMap['ID']->getWrappedType());
        $this->assertInstanceOf(StringType::class, $fieldMap['MyField']);
        $this->assertInstanceOf(IntType::class, $fieldMap['MyInt']);
    }

    public function testUpdateOperationPermissionCheck()
    {
        $update = new Update(RestrictedDataObjectFake::class);
        $restrictedDataobject = RestrictedDataObjectFake::create();
        $ID = $restrictedDataobject->write();

        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeRestrictedDataObjectFake']), 'SilverStripeRestrictedDataObjectFake');
        $update->addToManager($manager);
        $scaffold = $update->scaffold($manager);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/Cannot edit/');

        $scaffold['resolve'](
            $restrictedDataobject,
            [
                'Input' => [
                    'ID' => $ID,
                ],
            ],
            ['currentUser' => Member::create()],
            new ResolveInfo([])
        );
    }
}
