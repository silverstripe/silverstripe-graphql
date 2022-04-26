<?php

namespace SilverStripe\GraphQL\Schema\BulkLoader;

/**
 * Loads classes based on fuzzy match of FQCN, e.g. App\Models\*
 */
class NamespaceLoader extends AbstractBulkLoader
{
    const IDENTIFIER = 'namespaceLoader';

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
                if (fnmatch($pattern ?? '', $class ?? '', FNM_NOESCAPE)) {
                    $isIncluded = true;
                    break;
                }
            }
            foreach ($this->excludeList as $pattern) {
                if (fnmatch($pattern ?? '', $class ?? '', FNM_NOESCAPE)) {
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
}
