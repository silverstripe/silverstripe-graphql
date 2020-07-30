<?php


namespace SilverStripe\GraphQL\QueryFilter;

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
