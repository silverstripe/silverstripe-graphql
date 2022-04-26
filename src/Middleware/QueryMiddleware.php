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
     * @return ExecutionResult|array Result either as an ExecutionResult object or raw array
     */
    public function process(Schema $schema, string $query, array $context, array $vars, callable $next);
}
