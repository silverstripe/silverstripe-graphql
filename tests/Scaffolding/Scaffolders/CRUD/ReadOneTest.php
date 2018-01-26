<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\CRUD;

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
        foreach(ReadOne::get_extensions() as $className) {
            ReadOne::remove_extension($className);
        }
    }

    public function testReadOneOperationResolver()
    {
        $read = new ReadOne(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'GraphQL_DataObjectFake']), 'GraphQL_DataObjectFake');
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

}
