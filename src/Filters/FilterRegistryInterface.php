<?php


namespace SilverStripe\GraphQL\Filters;

interface FilterRegistryInterface
{
    /**
     * @param $identifier
     * @return mixed
     */
    public function getFilterByIdentifier($identifier);

    /**
     * @return FilterInterface[]
     */
    public function getAll();

    /**
     * @param FilterInterface $filter
     * @param string|null $identifier
     * @return $this
     */
    public function addFilter(FilterInterface $filter, $identifier = null);

}