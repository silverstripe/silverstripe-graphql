<?php


namespace SilverStripe\GraphQL\Tests\Schema\BulkLoader;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\BulkLoader\Collection;
use SilverStripe\GraphQL\Schema\BulkLoader\NamespaceLoader;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1a;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A2;
use SilverStripe\GraphQL\Tests\Fake\SubFake\FakePage;

class NamespaceLoaderTest extends SapphireTest
{
    public function testNamespaceLoader()
    {
        $collection = Collection::createFromClassList([
            A::class,
            A1::class,
            A2::class,
            A1a::class,
            FakePage::class,
        ]);

        $loader = new NamespaceLoader(
            ['SilverStripe\GraphQL\Tests\Fake\*'],
            ['SilverStripe\GraphQL\Tests\Fake\SubFake\*']
        );

        $result = $loader->collect($collection)->getClasses();

        $this->assertEquals([
            A::class,
            A1::class,
            A2::class,
            A1a::class,
        ], $result);
    }
}
