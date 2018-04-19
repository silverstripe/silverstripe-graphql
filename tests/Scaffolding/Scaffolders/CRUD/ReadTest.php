<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding\Scaffolders\CRUD;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\StringType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;

class ReadTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class,
        RestrictedDataObjectFake::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        // Make sure we're only testing the native features
        foreach (Read::get_extensions() as $className) {
            Read::remove_extension($className);
        }
    }

    public function testReadOperationResolver()
    {
        $read = new Read(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'SilverStripeDataObjectFake']), 'SilverStripeDataObjectFake');
        $read->addToManager($manager);
        $scaffold = $read->scaffold($manager);

        DataObjectFake::get()->removeAll();

        $record1 = DataObjectFake::create();
        $record1->MyField = 'AA First';
        $ID1 = $record1->write();

        $record2 = DataObjectFake::create();
        $record2->MyField = 'ZZ Last';
        $ID2 = $record2->write();

        $record3 = DataObjectFake::create();
        $record3->MyField = 'BB Middle';
        $ID3 = $record3->write();

        $response = $scaffold['resolve'](
            null,
            [],
            [
                'currentUser' => Member::create(),
            ],
            new ResolveInfo([])
        );

        $this->assertArrayHasKey('edges', $response);
        /** @var DataList $edges */
        $edges = $response['edges'];
        $this->assertEquals([$ID1, $ID3, $ID2], $edges->column('ID'));
    }

    public function testReadOperationArgs()
    {
        $read = new Read(DataObjectFake::class);
        $read->addArg('MyField', 'String');
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'GraphQL_DataObjectFake']), 'GraphQL_DataObjectFake');
        $read->addToManager($manager);
        $scaffold = $read->scaffold($manager);

        $args = $scaffold['args'];
        $this->assertArrayHasKey('MyField', $args);
        $this->assertInstanceOf(StringType::class, $args['MyField']['type']);
    }
}
