<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Creators\QueryOperationCreator;
use SilverStripe\GraphQL\Scaffolding\Creators\PaginatedQueryOperationCreator;
use SilverStripe\GraphQL\Pagination\Connection;

/**
 * Scaffolds a GraphQL query field
 */
class QueryScaffolder extends OperationScaffolder implements ManagerMutatorInterface, ScaffolderInterface
{

	/**
	 * @var boolean
	 */
	protected $usePagination = true;

	/**
	 * @var array
	 */
	protected $sortableFields = [];

	/**
	 * @param boolean $bool
	 */
	public function setUsePagination($bool)
	{
		$this->usePagination = (boolean) $bool;

		return $this;
	}

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $operationType = $this->getCreator($manager);
        $manager->addQuery(
            $operationType->toArray(),
            $this->getName()
        );
    }

    /**
     * @param array $fields
     */
    public function addSortableFields($fields)
    {
    	$this->sortableFields = array_unique(
    		array_merge(
    			$this->sortableFields,
    			(array) $fields
    		)
    	);
    	
    	return $this;
    }

    /**
     * Creates a Connection for pagination
     * @return Connection
     */
    protected function createConnection()
    {
        return Connection::create($this->operationName)
            ->setArgs($this->createArgs())
            ->setSortableFields($this->sortableFields);
    }

    /**
     * @param Manager $manager
     * @return QueryOperationCreator
     */
    public function getCreator(Manager $manager)
    {
        if($this->usePagination) {
        	return new PaginatedQueryOperationCreator(
        		$manager,
        		$this->createConnection(),
        		$this->operationName,
        		$this->typeName,
        		$this->resolver
        	);
        }

        return new QueryOperationCreator(
            $manager,
            $this->operationName,
            $this->typeName,
            $this->resolver,
            $this->createArgs()
        );
    }
}

