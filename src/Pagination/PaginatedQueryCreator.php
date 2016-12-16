<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\QueryCreator;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * A helper class for making a paginated query. A paginated query uses the
 * {@link Connection} object type to encapsulate the edges, nodes and page
 * information.
 */
class PaginatedQueryCreator extends QueryCreator
{
    /**
     * @var SilverStripe\GraphQL\Pagination\Connection
     */
    protected $connection;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        parent::__construct($manager);

        $this->connection = $this->connection();
    }

    public function connection() 
    {
        throw new \Exception('Missing connection() definition on "'. get_class($this) .'"');
    }

    /**
     * @return array
     */
    public function args()
    {
        return $this->connection->args();
    }

    /**
     * @return Callable
     */
    public function type()
    {
        return function() {
            return $this->connection->toType();
        };
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        return $this->connection->resolve(
            $value,
            $args,
            $context,
            $info
        );
    }
}
