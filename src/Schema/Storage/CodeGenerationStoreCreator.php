<?php


namespace SilverStripe\GraphQL\Schema\Storage;


use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\CacheFactory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageCreator;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageInterface;
use SilverStripe\GraphQL\Schema\Schema;

class CodeGenerationStoreCreator implements SchemaStorageCreator
{
    public function createStore(Schema $schema): SchemaStorageInterface
    {
        $factory = Injector::inst()->create(CacheFactory::class, [$schema->getSchemaKey()]);
        $cache = $factory->create(CacheInterface::class);
        return CodeGenerationStore::create($schema, $cache);
    }
}
