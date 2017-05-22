<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\QueryCreator;
use GraphQL\Type\Definition\ResolveInfo;

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
     *
     * @return Connection
     */
    abstract public function createConnection();

    public function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->createConnection();
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
