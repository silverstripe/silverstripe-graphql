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
     * @return $this
     */
    public function addFilter(FilterInterface $filter);

}