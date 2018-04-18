<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding\Scaffolders\CRUD;

use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\StringType;
use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use GraphQL\Type\Definition\ObjectType;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\ReadOne;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\Security\Member;

class ReadOneTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class,
        RestrictedDataObjectFake::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        // Make sure we're only testing the native features
        foreach (ReadOne::get_extensions() as $className) {
            ReadOne::remove_extension($className);
        }
    }

    public function testReadOneOperationResolver()
    {
        $read = new ReadOne(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $read->addToManager($manager);
        $scaffold = $read->scaffold($manager);

        DataObjectFake::get()->removeAll();

        $record = DataObjectFake::create();
        $record->MyField = 'Test';
        $ID = $record->write();


        $response = $scaffold['resolve'](
            null,
            ['ID' => $ID],
            [
                'currentUser' => Member::create(),
            ],
            new ResolveInfo([])
        );
        $this->assertInstanceOf(DataObjectFake::class, $response);
        $this->assertEquals($ID, $response->ID);
        $this->assertEquals('Test', $response->MyField);
    }

    public function testReadOneOperationArgs()
    {
        $read = new ReadOne(DataObjectFake::class);
        $read->addArg('MyField', 'String');
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $read->addToManager($manager);
        $scaffold = $read->scaffold($manager);

        // Check all args
        $args = $scaffold['args'];
        $this->assertEquals(['ID', 'MyField'], array_keys($args));

        /** @var NonNull $idType */
        $idType = $args['ID']['type'];
        $this->assertInstanceOf(NonNull::class, $idType);
        $this->assertInstanceOf(IDType::class, $idType->getWrappedType());

        // Check custom arg
        $this->assertArrayHasKey('MyField', $args);
        $this->assertInstanceOf(StringType::class, $args['MyField']['type']);
    }
}
