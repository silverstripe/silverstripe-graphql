<?php


namespace SilverStripe\GraphQL\Schema\BulkLoader;

/**
 * Loads classes based on fuzzy match of FQCN, e.g. App\Models\*
 */
class NamespaceLoader extends AbstractBulkLoader
{
    const IDENTIFIER = 'namespaceLoader';

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
                if (!fnmatch($pattern, $class, FNM_NOESCAPE)) {
                    $collection->removeClass($class);
                    break;
                }
            }
            foreach ($this->excludeList as $pattern) {
                if (fnmatch($pattern, $class, FNM_NOESCAPE)) {
                    $collection->removeClass($class);
                    break;
                }
            }
        }

        return $collection;
    }
}
