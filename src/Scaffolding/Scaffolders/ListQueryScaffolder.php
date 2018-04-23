<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Pagination\Connection;
use Exception;

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
     * @var PaginationScaffolder
     */
    protected $paginationScaffolder;

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
     * @return int
     */
    public function getPaginationLimit()
    {
        return $this->defaultLimit;
    }

    /**
     * @param int $int
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
     * @return int
     */
    public function getMaximumPaginationLimit()
    {
        return $this->maximumLimit;
    }

    /**
     * @param int $int
     * @return $this
     */
    public function setMaximumPaginationLimit($int)
    {
        $this->maximumLimit = (int) $int;
        if ($this->getPaginationLimit() > (int) $int) {
            $this->setPaginationLimit($int);
        }

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

    /**
     * @param array $config
     * @return $this
     */
    public function applyConfig(array $config)
    {
        parent::applyConfig($config);
        if (isset($config['sortableFields'])) {
            $fields = $config['sortableFields'];
            if (is_array($fields)) {
                $this->addSortableFields($fields);
            } else {
                throw new InvalidArgumentException(sprintf(
                    'sortableFields must be an array (see %s)',
                    $this->getTypeName()
                ));
            }
        }
        if (isset($config['paginate'])) {
            $paginate = $config['paginate'];
            $this->setUsePagination($paginate);

            if (isset($paginate['maximumLimit'])) {
                $this->setMaximumPaginationLimit($paginate['maximumLimit']);
            }

            if (isset($paginate['limit'])) {
                $this->setPaginationLimit($paginate['limit']);
            } elseif (isset($paginate['defaultLimit'])) {
                $this->setPaginationLimit($paginate['defaultLimit']);
            }
        }

        return $this;
    }

    /**
     * @param Manager $manager
     * @throws Exception
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
            'name' => $this->getName(),
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
        return Connection::create($this->getName())
            ->setConnectionType(function () use ($manager) {
                return $this->getType($manager);
            })
            ->setConnectionResolver($this->createResolverFunction())
            ->setArgs($this->createArgs($manager))
            ->setSortableFields($this->sortableFields)
            ->setDefaultLimit($this->getPaginationLimit())
            ->setMaximumLimit($this->getMaximumPaginationLimit());
    }

    /**
     * @param Manager $manager
     * @return PaginationScaffolder
     */
    protected function getPaginationScaffolder(Manager $manager)
    {
        if (!$this->paginationScaffolder) {
            $this->paginationScaffolder = new PaginationScaffolder(
                $this->getName(),
                $manager,
                $this->createConnection($manager)
            );
        }

        return $this->paginationScaffolder;
    }
}
