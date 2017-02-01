<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Pagination\PaginatedQueryCreator;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Manager;

class PaginationScaffolder extends PaginatedQueryCreator
{

    /**
     * @param Manager $manager
     * @param  Connection $connection
     */
    public function __construct(Manager $manager, Connection $connection)
    {
        parent::__construct($manager);

        $this->connection = $connection;
    }

    /**
     * Connection is passed in through the constructor argument,
     * to allow the instance to be created by the external scaffolding logic.
     *
     * @return Connection
     */
    public function createConnection()
    {
        return $this->connection;
    }
}
