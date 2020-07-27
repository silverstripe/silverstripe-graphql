<?php


namespace SilverStripe\GraphQL\QueryFilter;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;

trait QueryFilterAware
{

    /**
     * @var DataObjectQueryFilter
     */
    private $queryFilter;

    /**
     * @param DataObjectQueryFilter $filter
     * @return $this
     */
    public function setQueryFilter(DataObjectQueryFilter $filter)
    {
        $this->queryFilter = $filter;

        return $this;
    }

    /**
     * @return DataObjectQueryFilter
     */
    public function queryFilter()
    {
        return $this->queryFilter;
    }
}
