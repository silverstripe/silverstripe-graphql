<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter;

/**
 * Filters for queries are registered as services and retrieved by identifiers.
 * Registries must implement this interface.
 */
interface FilterRegistryInterface
{
    public function getFilterByIdentifier(string $identifier): ?FieldFilterInterface;

    /**
     * @return FieldFilterInterface[]
     */
    public function getAll();

    public function addFilter(FieldFilterInterface $filter, ?string $identifier = null): self;
}
