<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\CRUD;

use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeCRUDExtension;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Delete;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Security\Member;
use Exception;

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
        foreach(Delete::get_extensions() as $className) {
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
        $manager->addType(new ObjectType(['name' => 'GraphQL_DataObjectFake']), 'GraphQL_DataObjectFake');
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
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'GraphQL_DataObjectFake']), 'GraphQL_DataObjectFake');

        $scaffold = $delete->scaffold($manager);

        $this->assertArrayHasKey('IDs', $scaffold['args']);
        $this->assertInstanceof(NonNull::class, $scaffold['args']['IDs']['type']);

        $listOf = $scaffold['args']['IDs']['type']->getWrappedType();

        $this->assertInstanceOf(ListOfType::class, $listOf);

        $idType = $listOf->getWrappedType();

        $this->assertInstanceof(IDType::class, $idType);
    }

    public function testDeleteOperationPermissionCheck()
    {
        $delete = new Delete(RestrictedDataObjectFake::class);
        $restrictedDataobject = RestrictedDataObjectFake::create();
        $ID = $restrictedDataobject->write();
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'GraphQL_RestrictedDataObjectFake']), 'GraphQL_RestrictedDataObjectFake');

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
