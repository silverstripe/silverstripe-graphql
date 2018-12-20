<?php

namespace SilverStripe\GraphQL\Middleware;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;

/**
 * Represents middleware for evaluating a graphql query
 */
interface QueryMiddleware
{
    /**
     * @param Schema $schema
     * @param string $query
     * @param array $context
     * @param array $params
     * @param callable $next
     * @return ExecutionResult|array Result either as an ExecutionResult object or raw array
     */
    public function process(Schema $schema, $query, $context, $params, callable $next);
}
