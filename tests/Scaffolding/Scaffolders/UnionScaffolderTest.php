<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders;

use SilverStripe\GraphQL\Scaffolding\Scaffolders\UnionScaffolder;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use Exception;

class UnionScaffolderTest extends SapphireTest
{
    public function testUnionScaffolder()
    {
        $manager = new Manager();
        $scaffolder1 = new DataObjectScaffolder(FakeRedirectorPage::class);
        $scaffolder1->addFields(['RedirectionType']);
        $scaffolder1->addToManager($manager);

        $scaffolder2 = new DataObjectScaffolder(FakeSiteTree::class);
        $scaffolder2->addFields(['Title']);
        $scaffolder2->addToManager($manager);

        $scaffolder = new UnionScaffolder('test', [
            $scaffolder1->typeName(),
            $scaffolder2->typeName()
        ]);

        $unionType = $scaffolder->scaffold($manager);
        $types = $unionType->getTypes();

        $this->assertEquals($scaffolder1->typeName(), $types[0]->config['name']);
        $this->assertEquals($scaffolder2->typeName(), $types[1]->config['name']);

        $typeResolver = $unionType->getResolveTypeFn();
        $result = $typeResolver(new FakeRedirectorPage());

        $this->assertEquals($scaffolder1->typeName(), $result->config['name']);

        $result = $typeResolver(new FakeSiteTree());
        $this->assertEquals($scaffolder2->typeName(), $result->config['name']);

        // FakePage was never added. Should fall back on the parent type (FakeSiteTree)
        $result = $typeResolver(new FakePage());
        $this->assertEquals($scaffolder2->typeName(), $result->config['name']);

        $ex = null;
        try {
            $typeResolver(new Manager());
        } catch (Exception $e) {
            $ex = $e->getMessage();
        }

        $this->assertRegExp('/not a DataObject/', $ex);

        $ex = null;
        try {
            $typeResolver(new RestrictedDataObjectFake());
        } catch (Exception $e) {
            $ex = $e->getMessage();
        }
        
        $this->assertRegExp('/no type defined/', $ex);



    }
}
