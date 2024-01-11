<?php


namespace SilverStripe\GraphQL\Extensions;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Queries\SQLSelect;

/**
 * Attaches itself to {@see DataQuery} and records any classes that are queried within a closure context.
 * Allows code to measure and detect affected classes within any operation. E.g. for caching.
 *
 * @extends DataExtension<DataObject>
 */
class QueryRecorderExtension extends DataExtension
{
    use Injectable;

    /**
     * List of scopes, each of which contains a list of classes mapped from lowercase class name to cased class name
     *
     * @var string[][]
     */
    protected $levels = [];

    /**
     * Record query against a given class.
     */
    public function augmentDataQueryCreation(SQLSelect $select, DataQuery $query): void
    {
        // Skip if disabled
        if (empty($this->levels)) {
            return;
        }

        // Add class to all nested levels
        $class = $query->dataClass();
        for ($i = 0; $i < count($this->levels ?? []); $i++) {
            $this->levels[$i][strtolower($class)] = $class;
        }
    }

    /**
     * Create a new nesting level, record all classes queried during the callback, and unnest.
     * Returns an array containing [ $listOfClasses, $resultOfCallback ]
     *
     * @return array Two-length array with list of classes and result of callback
     */
    public function recordClasses(callable $callback): array
    {
        // Create nesting level
        $this->levels[] = [];
        try {
            $result = $callback();
            $classes = end($this->levels);
            return [$classes, $result];
        } finally {
            // Reset scope after callback completes
            array_pop($this->levels);
        }
    }
}
