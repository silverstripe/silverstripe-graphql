<?php


namespace SilverStripe\GraphQL\Tests\Schema\BulkLoader;

use Fake\FakeBulkLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\BulkLoader\AbstractBulkLoader;
use SilverStripe\GraphQL\Schema\BulkLoader\BulkLoaderSet;
use SilverStripe\GraphQL\Schema\BulkLoader\Collection;

class BulkLoaderSetTest extends SapphireTest
{
    public function testApplyConfig()
    {
        $set = new BulkLoaderSet();
        $set->applyConfig([
            'fake' => [
                'include' => [],
            ]
        ]);

        foreach ($set->getLoaders() as $loader) {
            $this->assertInstanceOf(AbstractBulkLoader::class, $loader);
        }
    }

    public function testInvalidLoader()
    {
        $this->expectExceptionMessage('Loader "fail" does not exist');
        $set = new BulkLoaderSet();
        $set->applyConfig([
            'fail' => [
                'include' => [],
            ]
        ]);
    }

    public function testProcess()
    {
        $set = new BulkLoaderSet([
            new FakeBulkLoader(['one' => 'one', 'two' => 'two']),
            new FakeBulkLoader(['three' => 'three', 'four' => 'four']),
        ], new Collection());
        $result = $set->process();
        $this->assertEquals(['three', 'four'], $result->getClasses());
    }

    public function testInitialCollection()
    {
        $set = new BulkLoaderSet([
            new FakeBulkLoader(),
            new FakeBulkLoader(),
        ], new Collection(['foo' => 'foo', 'bar' => 'bar']));
        $result = $set->process();
        $this->assertEquals(['foo', 'bar'], $result->getClasses());
    }
}
