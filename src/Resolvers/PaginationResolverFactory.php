<?php

namespace SilverStripe\GraphQL\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverFactory;
use Closure;
use Serializable;

class PaginationResolverFactory implements ResolverFactory
{
    use Injectable;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * PaginationResolverFactory constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Closure
     */
    public function createResolver()
    {
        $connection = $this->connection;
        return function ($obj, array $args, $context, ResolveInfo $info) use ($connection) {
            return $connection->resolve($obj, $args, $context, $info);
        };
    }

}