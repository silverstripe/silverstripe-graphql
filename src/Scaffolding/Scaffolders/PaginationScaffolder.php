<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Pagination\PaginatedQueryCreator;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Manager;

class PaginationScaffolder extends PaginatedQueryCreator
{

	public function __construct(Manager $manager, Connection $connection)
	{
		$this->manager = $manager;
		$this->connection = $connection;
	}

	public function connection()
	{
		return $this->connection;
	}
}