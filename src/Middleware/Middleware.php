<?php

namespace SilverStripe\GraphQL\Middleware;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;

/**
 * Represents middleware for evaluating a graphql query
 */
interface Middleware
{
    /**
     * @param callable $next
     * @param array $params
     */
    public function process(array $params, callable $next);
}
