<?php


namespace SilverStripe\GraphQL\QueryFilter;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;

trait QueryFilterAware
{

    /**
     * @var DataObjectQueryFilter
     */
    protected $queryFilter;

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

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        if ($this->queryFilter()->exists()) {
            $manager->addType(
                $this->queryFilter->getInputType(
                    $this->inputTypeName(Read::FILTER)
                )
            );
            $manager->addType(
                $this->queryFilter->getInputType(
                    $this->inputTypeName(Read::EXCLUDE)
                )
            );
        }

        parent::addToManager($manager);
    }
}
