<?php


namespace SilverStripe\GraphQL\Schema\BulkLoader;

use InvalidArgumentException;

/**
 * Loads classes that are in a given inheritance tree, e.g. MyApp\Models\Page
 */
class InheritanceLoader extends AbstractBulkLoader
{
    const IDENTIFIER = 'inheritanceLoader';

    /**
     * @return string
     */
    public static function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @param Collection $collection
     * @return Collection
     */
    public function collect(Collection $collection): Collection
    {
        foreach ($collection->getClasses() as $class) {
            foreach ($this->includeList as $pattern) {
                if ($class !== $pattern && !is_subclass_of($class, $pattern)) {
                    $collection->removeClass($class);
                    break;
                }
            }
            foreach ($this->excludeList as $pattern) {
                if ($class === $pattern || is_subclass_of($class, $pattern)) {
                    $collection->removeClass($class);
                    break;
                }
            }
        }

        return $collection;
    }

    /**
     * @param array $include
     * @return AbstractBulkLoader
     */
    public function include(array $include): AbstractBulkLoader
    {
        foreach ($include as $class) {
            if (!class_exists($class)) {
                throw new InvalidArgumentException(sprintf(
                    'Class %s given to %s does not exist',
                    $class,
                    static::class
                ));
            }
        }
        return parent::include($include);
    }

    /**
     * @param array $exclude
     * @return AbstractBulkLoader
     */
    public function exclude(array $exclude): AbstractBulkLoader
    {
        foreach ($exclude as $class) {
            if (!class_exists($class)) {
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
