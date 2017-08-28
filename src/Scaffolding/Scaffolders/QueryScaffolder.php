<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

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
     * @var int
     */
    protected $defaultLimit = 100;

    /**
     * @var int
     */
    protected $maximumLimit = 100;

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
     * @param $int
     * @return $this
     */
    public function setPaginationLimit($int)
    {
        if ((int) $int > $this->maximumLimit) {
            $int = $this->maximumLimit;
        }
        $this->defaultLimit = (int) $int;

        return $this;
    }

    /**
     * @param $int
     * @return $this
     */
    public function setMaximumPaginationLimit($int)
    {
        $this->maximumLimit = (int) $int;

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

            if (isset($config['paginate']['maximumLimit'])) {
                $this->setMaximumPaginationLimit((int) $config['paginate']['maximumLimit']);
            }

            if (isset($config['paginate']['limit'])) {
                $this->setPaginationLimit((int) $config['paginate']['limit']);
            } else if (isset($config['paginate']['defaultLimit'])) {
                $this->setPaginationLimit((int) $config['paginate']['defaultLimit']);
            }
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
            ->setSortableFields($this->sortableFields)
            ->setDefaultLimit($this->defaultLimit)
            ->setMaximumLimit($this->maximumLimit);
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
