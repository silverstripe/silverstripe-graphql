<?php

namespace SilverStripe\GraphQL\Pagination;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\QueryCreator;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * A helper class for making a paginated query. A paginated query uses the {@link Connection} object type to encapsulate
 * the edges, nodes and page information.
 */
class PaginatedQueryCreator extends QueryCreator {

    protected $connection;

    public function __construct(Manager $manager) {
        parent::__construct($manager);

        $this->connection = $this->connection();
    }

    public function connection() {
        throw new \Exception('Missing connection() definition on "'. get_class(this) .'"');
    }

    public function args() {
        return $this->connection->args();
    }

    public function type()
    {
        return function() {
            return $this->connection->toType();
        };
    }

    public function resolve($value, $args, $context, ResolveInfo $info) {
        return $this->connection->resolve(
            $value,
            $args,
            $context,
            $info
        );
    }
}
