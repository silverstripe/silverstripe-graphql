<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Pagination\Connection;

/**
 * Scaffolds a GraphQL query field.
 */
class ListQueryScaffolder extends QueryScaffolder
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
     * @var PaginationScaffolder
     */
    protected $paginationScaffolder;

    /**
     * @param bool $bool
     * @return $this
     */
    public function setUsePagination($bool)
    {
        $this->usePagination = (bool)$bool;

        return $this;
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
                (array)$fields
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
            $this->setUsePagination((bool)$config['paginate']);
        }

        return $this;
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        if ($this->usePagination) {
            $paginationScaffolder = $this->getPaginationScaffolder($manager);
            $paginationScaffolder->addToManager($manager);
        }

        parent::addToManager($manager);
    }

    /**
     * @param Manager $manager
     *
     * @return array
     */
    public function scaffold(Manager $manager)
    {
        if ($this->usePagination) {
            $paginationScaffolder = $this->getPaginationScaffolder($manager);

            return $paginationScaffolder->scaffold($manager);
        }

        return [
            'name' => $this->operationName,
            'args' => $this->createArgs($manager),
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
            ->setConnectionType(function () use ($manager) {
                return $this->getType($manager);
            })
            ->setConnectionResolver($this->createResolverFunction())
            ->setArgs($this->createArgs($manager))
            ->setSortableFields($this->sortableFields);
    }

    /**
     * @param Manager $manager
     * @return PaginationScaffolder
     */
    protected function getPaginationScaffolder(Manager $manager)
    {
        if (!$this->paginationScaffolder) {
            $this->paginationScaffolder = new PaginationScaffolder(
                $this->operationName,
                $manager,
                $this->createConnection($manager)
            );
        }

        return $this->paginationScaffolder;
    }
}
