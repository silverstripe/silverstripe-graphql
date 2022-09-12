<?php


namespace SilverStripe\GraphQL\Tests\Schema;

use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageCreator;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageInterface;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

class TestStoreCreator implements SchemaStorageCreator
{
    /**
     * @var string
     */
    public static $dir;

    public function createStore(string $name): SchemaStorageInterface
    {
        $cache = new Psr16Cache(new FilesystemAdapter);
        $store = CodeGenerationStore::create($name, $cache);
        $store->setRootDir(static::$dir);

        return $store;
    }
}
