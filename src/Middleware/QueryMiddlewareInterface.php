<?php

namespace SilverStripe\GraphQL\Middleware;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\OperationParams;
use GraphQL\Server\ServerConfig;

/**
 * Represents middleware for evaluating a graphql query
 */
interface QueryMiddlewareInterface
{
    /**
     * @param OperationParams[] $operations
     * @param ServerConfig $config
     * @param callable $next
     * @return ExecutionResult[]|ExecutionResult
     */
    public function process(array $operations, ServerConfig $config, callable $next);
}
