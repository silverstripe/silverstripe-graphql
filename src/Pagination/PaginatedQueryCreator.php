<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\GraphQL\Manager;
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
     * @var Connection
     */
    protected $connection;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager, Connection $connection = null)
    {
        parent::__construct($manager);

        $this->connection = $connection ?: $this->connection();
    }

    /**
     * Get connection for this query
     *
     * @return Connection
     */
    abstract public function connection();

    /**
     * @return array
     */
    public function args()
    {
        return $this->connection->args();
    }

    public function type()
    {
        return function () {
            return $this->connection->toType();
        };
    }

    public function resolve($value, array $args, $context, ResolveInfo $info)
    {
        return $this->connection->resolve(
            $value,
            $args,
            $context,
            $info
        );
    }
}
