<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\CRUD;

use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use GraphQL\Type\Definition\ObjectType;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use SilverStripe\Security\Member;

class ReadTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class,
        RestrictedDataObjectFake::class,
    ];

    public function testReadOperationResolver()
    {
        $read = new Read(DataObjectFake::class);
        $manager = new Manager();
        $manager->addType(new ObjectType(['name' => 'GraphQL_DataObjectFake']), 'GraphQL_DataObjectFake');

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
        $this->assertEquals([$ID1, $ID3, $ID2], $response['edges']->column('ID'));
    }

    public function testUnionInheritance()
    {
        $redirectorScaffold = new DataObjectScaffolder(FakeRedirectorPage::class);
        $redirectorScaffold->addToManager($manager = new Manager());
        $read = new Read(FakeRedirectorPage::class);
        $read->setUsePagination(false);

        $scaffold = $read->scaffold($manager);
        $type = $scaffold['type']->getWrappedType();
        $this->assertEquals(
            $redirectorScaffold->typeName(),
            $type->config['name']
        );

        $pageScaffold = new DataObjectScaffolder(FakePage::class);
        $pageScaffold->addToManager($manager);

        $read = new Read(FakePage::class);
        $read->setUsePagination(false);

        $scaffold = $read->scaffold($manager);
        $unionType = $scaffold['type']->getWrappedType();
        $this->assertEquals(
            $pageScaffold->typeName().'WithDescendants',
            $unionType->name
        );
        $types = $unionType->getTypes();
        $this->assertEquals(
            $pageScaffold->typeName(),
            $types[0]->config['name']
        );
        $this->assertEquals(
            $redirectorScaffold->typeName(),
            $types[1]->config['name']
        );
    }
}
