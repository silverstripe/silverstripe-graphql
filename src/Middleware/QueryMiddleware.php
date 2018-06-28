<?php

namespace SilverStripe\GraphQL\Middleware;

use GraphQL\Executor\ExecutionResult;

/**
 * Represents middleware for evaluating a graphql query
 */
interface QueryMiddleware
{

    /**
     * @param string $query
     * @param array $params
     * @param callable $next
     * @return ExecutionResult|array Result either as an ExecutionResult object or raw array
     */
    public function process($query, $params, callable $next);
}
