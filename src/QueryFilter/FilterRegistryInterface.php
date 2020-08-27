<?php


namespace SilverStripe\GraphQL\QueryFilter;

/**
 * Filters for queries are registered as services and retrieved by identifiers.
 * Registries must implement this interface.
 */
interface FilterRegistryInterface
{
    /**
     * @param $identifier
     * @return FieldFilterInterface|null
     */
    public function getFilterByIdentifier($identifier): ?FieldFilterInterface;

    /**
     * @return FieldFilterInterface[]
     */
    public function getAll();

    /**
     * @param FieldFilterInterface $filter
     * @param string|null $identifier
     * @return $this
     */
    public function addFilter(FieldFilterInterface $filter, $identifier = null);
}
