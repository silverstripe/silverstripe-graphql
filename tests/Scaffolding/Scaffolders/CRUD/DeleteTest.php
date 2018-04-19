<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding\Scaffolders\CRUD;

use Exception;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\StringType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Delete;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeCRUDExtension;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\Security\Member;

class DeleteTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class,
        RestrictedDataObjectFake::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        // Make sure we're only testing the native features
        foreach (Delete::get_extensions() as $className) {
            Delete::remove_extension($className);
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
     */
    public function testDeleteOperationResolver($shouldExtend)
    {
        if ($shouldExtend) {
            Delete::add_extension(FakeCRUDExtension::class);
        }

        $delete = new Delete(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $delete->addToManager($manager);
        $scaffold = $delete->scaffold($manager);

        $record = DataObjectFake::create();
        $ID1 = $record->write();

        $record = DataObjectFake::create();
        $ID2 = $record->write();

        $record = DataObjectFake::create();
        $ID3 = $record->write();

        $scaffold['resolve'](
            $record,
            [
                'IDs' => [$ID1, $ID2],
            ],
            [
                'currentUser' => Member::create(),
            ],
            new ResolveInfo([])
        );

        if ($shouldExtend) {
            $this->assertNotNull(DataObjectFake::get()->byID($ID1));
            $this->assertNotNull(DataObjectFake::get()->byID($ID2));
            $this->assertInstanceOf(DataObjectFake::class, DataObjectFake::get()->byID($ID3));
        } else {
            $this->assertNull(DataObjectFake::get()->byID($ID1));
            $this->assertNull(DataObjectFake::get()->byID($ID2));
            $this->assertInstanceOf(DataObjectFake::class, DataObjectFake::get()->byID($ID3));
        }
    }

    public function testDeleteOperationArgs()
    {
        $delete = new Delete(DataObjectFake::class);
        $delete->addArg('MyField', 'String');
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $delete->addToManager($manager);
        $scaffold = $delete->scaffold($manager);

        // Test args
        $args = $scaffold['args'];
        $this->assertEquals(['IDs', 'MyField'], array_keys($args));

        /** @var NonNull $idType */
        $idType = $args['IDs']['type'];
        $this->assertInstanceOf(NonNull::class, $idType);
        /** @var ListOfType $idTypeWrapped */
        $idTypeWrapped = $idType->getWrappedType();
        $this->assertInstanceOf(ListOfType::class, $idTypeWrapped);
        $this->assertInstanceOf(IDType::class, $idTypeWrapped->getWrappedType());

        // Custom field
        $this->assertArrayHasKey('MyField', $args);
        $this->assertInstanceOf(StringType::class, $args['MyField']['type']);
    }

    public function testDeleteOperationPermissionCheck()
    {
        $delete = new Delete(RestrictedDataObjectFake::class);
        $restrictedDataobject = RestrictedDataObjectFake::create();
        $ID = $restrictedDataobject->write();
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeRestrictedDataObjectFake']), 'SilverStripeRestrictedDataObjectFake');

        $scaffold = $delete->scaffold($manager);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/Cannot delete/');

        $scaffold['resolve'](
            $restrictedDataobject,
            ['IDs' => [$ID]],
            ['currentUser' => Member::create()],
            new ResolveInfo([])
        );
    }
}
