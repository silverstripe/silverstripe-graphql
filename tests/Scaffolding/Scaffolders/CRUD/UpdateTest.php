<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\CRUD;

use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
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

    public function testUpdateOperationResolver()
    {
        $update = new Update(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'GraphQL_DataObjectFake']), 'GraphQL_DataObjectFake');

        $scaffold = $update->scaffold($manager);

        $record = DataObjectFake::create([
            'MyField' => 'old',
        ]);
        $ID = $record->write();

        $scaffold['resolve'](
            $record,
            [
                'ID' => $ID,
                'Input' => ['MyField' => 'new'],
            ],
            [
                'currentUser' => Member::create(),
            ],
            new ResolveInfo([])
        );
        $updatedRecord = DataObjectFake::get()->byID($ID);
        $this->assertEquals('new', $updatedRecord->MyField);
    }

    public function testUpdateOperationInputType()
    {
        $update = new Update(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'GraphQL_DataObjectFake']), 'GraphQL_DataObjectFake');

        $scaffold = $update->scaffold($manager);

        $this->assertArrayHasKey('Input', $scaffold['args']);
        $this->assertInstanceof(NonNull::class, $scaffold['args']['Input']['type']);

        $config = $scaffold['args']['Input']['type']->getWrappedType()->config;

        $this->assertEquals('GraphQL_DataObjectFakeUpdateInputType', $config['name']);
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

    public function testUpdateOperationPermissionCheck()
    {
        $update = new Update(RestrictedDataObjectFake::class);
        $restrictedDataobject = RestrictedDataObjectFake::create();
        $ID = $restrictedDataobject->write();

        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'GraphQL_RestrictedDataObjectFake']), 'GraphQL_RestrictedDataObjectFake');

        $scaffold = $update->scaffold($manager);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/Cannot edit/');

        $scaffold['resolve'](
            $restrictedDataobject,
            ['ID' => $ID],
            ['currentUser' => Member::create()],
            new ResolveInfo([])
        );
    }
}
