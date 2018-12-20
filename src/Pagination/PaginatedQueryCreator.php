<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\QueryCreator;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Container\NotFoundExceptionInterface;

/**
 * A helper class for making a paginated query. A paginated query uses the
 * {@link Connection} object type to encapsulate the edges, nodes and page
 * information.
 */
abstract class PaginatedQueryCreator extends QueryCreator implements OperationResolver
{

    /**
     * @var Connection Local instance created through `createConnection()`.
     */
    protected $connection;

    /**
     * Get connection for this query
     * @param Manager $manager
     * @return Connection
     */
    abstract public function createConnection(Manager $manager);

    public function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->createConnection($this->manager);
        }

        return $this->connection;
    }

    /**
     * @return array
     */
    public function args()
    {
        return $this->getConnection()->args();
    }

    /**
     * @return array
     * @throws NotFoundExceptionInterface
     */
    public function extraTypes()
    {
        return $this->getConnection()->extraTypes();
    }

    public function type()
    {
        return $this->getConnection()->toType();
    }

    public function resolve($value, array $args, $context, ResolveInfo $info)
    {
        return $this->getConnection()->resolve(
            $value,
            $args,
            $context,
            $info
        );
    }
}
