<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use InvalidArgumentException;

/**
 * Scaffolds a GraphQL query field.
 */
class QueryScaffolder extends OperationScaffolder implements ManagerMutatorInterface, ScaffolderInterface
{
    /**
     * @var bool
     */
    protected $usePagination = true;

    /**
     * @var array
     */
    protected $sortableFields = [];

    /**
     * @param bool $bool
     * @return $this
     */
    public function setUsePagination($bool)
    {
        $this->usePagination = (bool) $bool;

        return $this;
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $manager->addQuery(function () use ($manager) {
            return $this->scaffold($manager);
        }, $this->getName());
    }

    /**
     * @param array $fields
     * @return $this
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

    public function applyConfig(array $config)
    {
        parent::applyConfig($config);
        if (isset($config['sortableFields'])) {
            if (is_array($config['sortableFields'])) {
                $this->addSortableFields($config['sortableFields']);
            } else {
                throw new InvalidArgumentException(sprintf(
                    'sortableFields must be an array (see %s)',
                    $this->typeName
                ));
            }
        }
        if (isset($config['paginate'])) {
            $this->setUsePagination((bool) $config['paginate']);
        }

        return $this;
    }

    /**
     * @param Manager $manager
     *
     * @return array
     */
    public function scaffold(Manager $manager)
    {
        if ($this->usePagination) {
            return (new PaginationScaffolder(
                $manager,
                $this->createConnection($manager)
            ))->toArray();
        }

        return [
            'name' => $this->operationName,
            'args' => $this->createArgs(),
            'type' => Type::listOf($this->getType($manager)),
            'resolve' => $this->createResolverFunction(),
        ];
    }

    /**
     * Creates a Connection for pagination.
     *
     * @param Manager $manager
     * @return Connection
     */
    protected function createConnection(Manager $manager)
    {
        return Connection::create($this->operationName)
            ->setConnectionType($this->getType($manager))
            ->setConnectionResolver($this->createResolverFunction())
            ->setArgs($this->createArgs())
            ->setSortableFields($this->sortableFields);
    }

    /**
     * Creates a thunk that lazily fetches the type
     *
     * @param Manager $manager
     * @return ObjectType
     */
    protected function getType(Manager $manager)
    {
        /** @var ObjectType $type */
        $type = $manager->getType($this->typeName);
        return $type;
    }
}
