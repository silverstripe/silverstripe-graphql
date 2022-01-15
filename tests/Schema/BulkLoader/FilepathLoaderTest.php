<?php


namespace SilverStripe\GraphQL\Tests\Schema\BulkLoader;

use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\BulkLoader\Collection;
use SilverStripe\GraphQL\Schema\BulkLoader\FilepathLoader;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A1a;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A2;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B1;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B1a;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\B2;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\C;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\C1;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\C2;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\C2a;

class FilepathLoaderTest extends SapphireTest
{
    public function testFilepathLoader()
    {

        $collection = Collection::createFromClassList([
            A::class,
            A1::class,
            A2::class,
            A1a::class,
            B::class,
            B1::class,
            B2::class,
            B1a::class,
            C::class,
            C1::class,
            C2::class,
            C2a::class,
        ]);

        $loader = new FilepathLoader(
            ['silverstripe/graphql: tests/Fake/Inheritance/B*.php'],
            ['silverstripe/graphql: tests/Fake/Inheritance/*a.php']
        );

        $result = $loader->collect($collection)->getClasses();

        $this->assertEquals([
            B::class,
            B1::class,
            B2::class,
        ], $result);
    }
}
