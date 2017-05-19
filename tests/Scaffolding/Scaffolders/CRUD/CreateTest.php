<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\CRUD;

use GraphQL\Type\Definition\ObjectType;
use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Create;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Security\Member;
use Exception;

class CreateTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        'SilverStripe\GraphQL\Tests\Fake\DataObjectFake',
        'SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake',
    ];

    public function testCreateOperationResolver()
    {
        $create = new Create(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'Data_Object_Fake']), 'Data_Object_Fake');
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

        $this->assertGreaterThan(0, $newRecord->ID);
        $this->assertEquals('__testing__', $newRecord->MyField);
    }

    public function testCreateOperationInputType()
    {
        $create = new Create(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'Data_Object_Fake']), 'Data_Object_Fake');
        $scaffold = $create->scaffold($manager);

        $this->assertArrayHasKey('Input', $scaffold['args']);
        $this->assertInstanceof(NonNull::class, $scaffold['args']['Input']['type']);

        $config = $scaffold['args']['Input']['type']->getWrappedType()->config;

        $this->assertEquals('Data_Object_FakeCreateInputType', $config['name']);
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
        $create = new Create(RestrictedDataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'Restricted_Data_Object_Fake']), 'Restricted_Data_Object_Fake');

        $scaffold = $create->scaffold($manager);

        $this->setExpectedExceptionRegExp(
            Exception::class,
            '/Cannot create/'
        );

        $scaffold['resolve'](
            null,
            [],
            ['currentUser' => Member::create()],
            new ResolveInfo([])
        );
    }
}
