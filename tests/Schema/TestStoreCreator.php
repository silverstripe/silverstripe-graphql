<?php


namespace SilverStripe\GraphQL\Tests\Schema;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\CacheFactory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageCreator;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageInterface;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use Symfony\Component\Cache\Simple\FilesystemCache;

class TestStoreCreator implements SchemaStorageCreator
{
    /**
     * @var string
     */
    public static $dir;


    public function createStore(string $name): SchemaStorageInterface
    {
        $store = new CodeGenerationStore($name, new FilesystemCache());
        $store->setRootDir(static::$dir);

        return $store;
    }
}
