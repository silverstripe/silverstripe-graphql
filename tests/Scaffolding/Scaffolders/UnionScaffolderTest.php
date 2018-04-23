<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\Scaffolding;

use SilverStripe\GraphQL\Scaffolding\Scaffolders\UnionScaffolder;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\RestrictedDataObjectFake;
use GraphQL\Type\Definition\ResolveInfo;
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
            $scaffolder1->getTypeName(),
            $scaffolder2->getTypeName()
        ]);

        $unionType = $scaffolder->scaffold($manager);
        $types = $unionType->getTypes();

        $this->assertEquals($scaffolder1->getTypeName(), $types[0]->config['name']);
        $this->assertEquals($scaffolder2->getTypeName(), $types[1]->config['name']);

        $fakeRedirector = new FakeRedirectorPage();
        $result = $unionType->resolveType($fakeRedirector, [], new ResolveInfo([]));
        //$result = $typeResolver(new FakeRedirectorPage());

        $this->assertEquals($scaffolder1->getTypeName(), $result->config['name']);

        $result = $unionType->resolveType(new FakeSiteTree(), [], new ResolveInfo([]));
        $this->assertEquals($scaffolder2->getTypeName(), $result->config['name']);

        // FakePage was never added. Should fall back on the parent type (FakeSiteTree)
        $result = $unionType->resolveType(new FakePage(), [], new ResolveInfo([]));
        $this->assertEquals($scaffolder2->getTypeName(), $result->config['name']);

        $ex = null;
        try {
            $unionType->resolveType(new Manager(), [], new ResolveInfo([]));
        } catch (Exception $e) {
            $ex = $e->getMessage();
        }

        $this->assertRegExp('/not a DataObject/', $ex);

        $ex = null;
        try {
            $unionType->resolveType(new RestrictedDataObjectFake(), [], new ResolveInfo([]));
        } catch (Exception $e) {
            $ex = $e->getMessage();
        }

        $this->assertRegExp('/no type defined/', $ex);
    }
}
