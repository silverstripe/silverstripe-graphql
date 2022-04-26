<?php

namespace SilverStripe\GraphQL\Schema\BulkLoader;

use InvalidArgumentException;
use SilverStripe\Core\Extension;
use SilverStripe\View\ViewableData;

/**
 * Loads classes that have a given extension assigned to them.
 */
class ExtensionLoader extends AbstractBulkLoader
{
    public const IDENTIFIER = 'extensionLoader';

    public static function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function collect(Collection $collection): Collection
    {
        $newCollection = parent::collect($collection);

        foreach ($collection->getClasses() as $class) {
            $isIncluded = false;
            foreach ($this->includeList as $pattern) {
                if (ViewableData::has_extension($class, $pattern)) {
                    $isIncluded = true;
                    break;
                }
            }
            foreach ($this->excludeList as $pattern) {
                if (ViewableData::has_extension($class, $pattern)) {
                    $isIncluded = false;
                    break;
                }
            }

            if (!$isIncluded) {
                $newCollection->removeClass($class);
            }
        }

        return $newCollection;
    }

    public function include(array $include): AbstractBulkLoader
    {
        foreach ($include as $class) {
            if (!class_exists($class ?? '') || !is_subclass_of($class, Extension::class)) {
                throw new InvalidArgumentException(sprintf(
                    'Class %s given to %s is not a valid extension',
                    $class,
                    static::class
                ));
            }
        }

        return parent::include($include);
    }

    public function exclude(array $exclude): AbstractBulkLoader
    {
        foreach ($exclude as $class) {
            if (!class_exists($class ?? '') || !is_subclass_of($class, Extension::class)) {
                throw new InvalidArgumentException(sprintf(
                    'Class %s given to %s is not a valid extension',
                    $class,
                    static::class
                ));
            }
        }

        return parent::exclude($exclude);
    }
}
