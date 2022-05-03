<?php

namespace SilverStripe\GraphQL\Schema\BulkLoader;

use InvalidArgumentException;

/**
 * Loads classes that are in a given inheritance tree, e.g. MyApp\Models\Page
 */
class InheritanceLoader extends AbstractBulkLoader
{
    const IDENTIFIER = 'inheritanceLoader';

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
                if ($class === $pattern || is_subclass_of($class, $pattern ?? '')) {
                    $isIncluded = true;
                    break;
                }
            }
            foreach ($this->excludeList as $pattern) {
                if ($class === $pattern || is_subclass_of($class, $pattern ?? '')) {
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
            if (!class_exists($class ?? '')) {
                throw new InvalidArgumentException(sprintf(
                    'Class %s given to %s does not exist',
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
            if (!class_exists($class ?? '')) {
                throw new InvalidArgumentException(sprintf(
                    'Class %s given to %s does not exist',
                    $class,
                    static::class
                ));
            }
        }

        return parent::exclude($exclude);
    }
}
