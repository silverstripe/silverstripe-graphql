<?php

namespace SilverStripe\GraphQL\Scaffolding\Creators;

use SilverStripe\GraphQL\Pagination\PaginatedQueryCreator;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Manager;

class PaginatedQueryOperationCreator extends PaginatedQueryCreator
{

	use PolymorphicResolverTrait;
	
	/**
	 * @var string
	 */
	protected $operationName;

	/**
	 * @param Manager    $manager       
	 * @param Connection $connection    
	 * @param string     $operationName 
	 * @param string     $typeName      
	 * @param Callable|ResolverInterface     $resolver  
	 */
	public function __construct(Manager $manager, Connection $connection, $operationName, $typeName, $resolver)
	{
		$this->connection = $connection;
		$this->resolver = $resolver;
		$this->operationName = $operationName;

		parent::__construct($manager);

        $this->connection
        	->setConnectionType(function() use ($typeName) {
           		return $this->manager->getType($typeName);
        	})
        	->setConnectionResolver($this->createResolverFunction());
	}

	/**
	 * @return array
	 */
	public function attributes()
	{
		return [
			'name' => $this->operationName
		];
	}

	/**
	 * This method usually builds a new Connection interface, but since it is
	 * assigned in the constructor of this class, it's overloaded as a simple accessor
	 * 
	 * @return SilverStripe\GraphQL\Pagination\Connection;
	 */
	public function connection()
	{
		return $this->connection;
	}
}