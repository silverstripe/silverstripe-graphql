<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\CRUD;

use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Create;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Security\Member;

class ReadTest extends SapphireTest
{
    protected $extraDataObjects = [
        'SilverStripe\GraphQL\Tests\Fake\DataObjectFake',
        'SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake',
    ];

    public function testReadOperationResolver()
    {
        $read = new Read(DataObjectFake::class);
        $scaffold = $read->scaffold(new Manager());

        DataObjectFake::get()->removeAll();

        $record = DataObjectFake::create();
        $ID1 = $record->write();

        $record = DataObjectFake::create();
        $ID2 = $record->write();

        $record = DataObjectFake::create();
        $ID3 = $record->write();

        $response = $scaffold['resolve'](
            null,
            [],
            [
                'currentUser' => Member::create(),
            ],
            new ResolveInfo([])
        );

        $this->assertArrayHasKey('edges', $response);
        $this->assertEquals([$ID1, $ID2, $ID3], $response['edges']->column('ID'));
    }

    public function testUnionInheritance()
    {
        $redirectorScaffold = new DataObjectScaffolder(FakeRedirectorPage::class);
        $redirectorScaffold->addToManager($manager = new Manager());
        $read = new Read(FakeRedirectorPage::class);
        $read->setUsePagination(false);

        $scaffold = $read->scaffold($manager);
        $type = $scaffold['type']();
        $this->assertEquals(
            $redirectorScaffold->typeName(),
            $type->config['name']
        );

        $pageScaffold = new DataObjectScaffolder(FakePage::class);
        $pageScaffold->addToManager($manager);

        $read = new Read(FakePage::class);
        $read->setUsePagination(false);

        $scaffold = $read->scaffold($manager);
        $unionType = $scaffold['type']();
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
