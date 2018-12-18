<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Error\Error;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Pagination\PaginatedQueryCreator;
use SilverStripe\GraphQL\Resolvers\PaginationResolverFactory;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition;
use Psr\Container\NotFoundExceptionInterface;

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
     * @param Manager $manager
     * @return Connection
     */
    public function createConnection(Manager $manager)
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
     * @throws Error
     * @return SerialisableFieldDefinition
     */
    public function scaffold(Manager $manager)
    {
        $connectionName = $this->connection->getConnectionTypeName();
        return SerialisableFieldDefinition::create([
            'name' => $this->operationName,
            'args' => $this->connection->args(),
            'type' => $manager->getType($connectionName),
            'resolverFactory' => PaginationResolverFactory::create($this->connection),
        ]);
    }

    /**
     * @param Manager $manager
     * @throws NotFoundExceptionInterface
     */
    public function addToManager(Manager $manager)
    {
        $manager->addType($this->connection->toType());
        foreach ($this->connection->getExtraTypes() as $type) {
            $manager->addType($type);
        }
    }
}
