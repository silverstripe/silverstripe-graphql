<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\CRUD;

use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Create;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Delete;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Security\Member;
use Exception;

class DeleteTest extends SapphireTest
{
    protected $extraDataObjects = [
        'SilverStripe\GraphQL\Tests\Fake\DataObjectFake',
        'SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake',
    ];

    public function testDeleteOperationResolver()
    {
        $delete = new Delete(DataObjectFake::class);
        $scaffold = $delete->scaffold(new Manager());

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

        $this->assertNull(DataObjectFake::get()->byID($ID1));
        $this->assertNull(DataObjectFake::get()->byID($ID2));
        $this->assertInstanceOf(DataObjectFake::class, DataObjectFake::get()->byID($ID3));
    }

    public function testDeleteOperationArgs()
    {
        $delete = new Delete(DataObjectFake::class);
        $scaffold = $delete->scaffold(new Manager());

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

        $scaffold = $delete->scaffold(new Manager());

        $this->setExpectedExceptionRegExp(
            Exception::class,
            '/Cannot delete/'
        );

        $scaffold['resolve'](
            $restrictedDataobject,
            ['IDs' => [$ID]],
            ['currentUser' => Member::create()],
            new ResolveInfo([])
        );
    }
}
