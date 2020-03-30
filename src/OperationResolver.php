<?php

namespace SilverStripe\GraphQL;

use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Standard resolve callback for Mutations or Queries
 */
interface OperationResolver
{
    /**
     * Invoked by the Executor class to resolve this mutation / query
     * @see Executor
     *
     * @param mixed $object
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolve($object, array $args, $context, ResolveInfo $info);
}
