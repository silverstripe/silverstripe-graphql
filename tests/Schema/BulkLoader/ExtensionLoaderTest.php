<?php


namespace SilverStripe\GraphQL\Tests\Schema\BulkLoader;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\BulkLoader\Collection;
use SilverStripe\GraphQL\Schema\BulkLoader\ExtensionLoader;
use SilverStripe\GraphQL\Tests\Fake\Extensions\FakeExtension1;
use SilverStripe\GraphQL\Tests\Fake\Extensions\FakeExtension2;
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

class ExtensionLoaderTest extends SapphireTest
{
    public function testExtensionLoader()
    {
        A1::add_extension(FakeExtension1::class);
        A2::add_extension(FakeExtension1::class);

        B1::add_extension(FakeExtension2::class);
        B2::add_extension(FakeExtension2::class);

        C1::add_extension(FakeExtension1::class);
        C1::add_extension(FakeExtension2::class);

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

        $loader = new ExtensionLoader(
            [FakeExtension1::class],
            [FakeExtension2::class]
        );

        $result = $loader->collect($collection)->getClasses();
        $this->assertEquals([
            A1::class,
            A2::class,
            A1a::class,
        ], $result);

        $loader = new ExtensionLoader(
            [FakeExtension2::class]
        );

        $result = $loader->collect($collection)->getClasses();
        $this->assertEquals([
            B1::class,
            B2::class,
            B1a::class,
            C1::class,
        ], $result);
    }
}
