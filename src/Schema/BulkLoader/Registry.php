<?php

namespace SilverStripe\GraphQL\Schema\BulkLoader;

use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;

/**
 * Frontend for creating a cached registry instance based on all the qualifying subclasses.
 */
class Registry
{
    private static ?RegistryBackend $inst = null;

    /**
     * @return RegistryBackend
     */
    public static function inst(): RegistryBackend
    {
        if (self::$inst) {
            return self::$inst;
        }
        $subclasses = array_values(ClassInfo::subclassesFor(AbstractBulkLoader::class, false) ?? []);
        $bulkLoaders = array_map(function ($className) {
            return Injector::inst()->get($className);
        }, $subclasses ?? []);

        self::$inst = RegistryBackend::create(...$bulkLoaders);

        return self::$inst;
    }
}
