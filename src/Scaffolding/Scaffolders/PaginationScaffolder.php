<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Pagination\PaginatedQueryCreator;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;

class PaginationScaffolder extends PaginatedQueryCreator implements ManagerMutatorInterface, ScaffolderInterface
{
    /**
     * @var string
     */
    protected $operationName;

    /**
     * @param string $operationName
     * @param Manager $manager
     * @param  Connection $connection
     */
    public function __construct($operationName, Manager $manager, Connection $connection)
    {
        parent::__construct($manager);
        $this->connection = $connection;
        $this->operationName = $operationName;
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

    /**
     * @return string
     */
    public function getOperationName()
    {
        return $this->operationName;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setOperationName($name)
    {
        $this->operationName = $name;

        return $this;
    }

    /**
     * @param Manager $manager
     * @return array
     */
    public function scaffold(Manager $manager)
    {
        $connectionName = $this->connection->getConnectionTypeName();
        return [
            'name' => $this->operationName,
            'args' => $this->connection->args(),
            'type' => $manager->getType($connectionName),
            'resolve' => function ($obj, array $args, $context, ResolveInfo $info) {
                return $this->connection->resolve($obj, $args, $context, $info);
            }
        ];
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $manager->addType($this->connection->toType());
    }
}
