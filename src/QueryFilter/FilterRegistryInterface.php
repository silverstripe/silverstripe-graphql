<?php


namespace SilverStripe\GraphQL\QueryFilter;

interface FilterRegistryInterface
{
    /**
     * @param $identifier
     * @return mixed
     */
    public function getFilterByIdentifier($identifier);

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
