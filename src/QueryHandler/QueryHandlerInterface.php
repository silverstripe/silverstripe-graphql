<?php


namespace SilverStripe\GraphQL\QueryHandler;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\OperationParams;
use GraphQL\Type\Schema as GraphQLSchema;
use SilverStripe\GraphQL\Schema\Interfaces\ContextProvider;

/**
 * Query handlers are responsible for applying a query as a string to a Schema object
 * and returning a result.
 */
interface QueryHandlerInterface
{
    /**
     * @param OperationParams[]|OperationParams $operations
     * @param GraphQLSchema $schema
     * @return ExecutionResult[]|ExecutionResult
     */
    public function executeOperations($operations, GraphQLSchema $schema);

    /**
     * @param ContextProvider $provider
     * @return $this
     */
    public function addContextProvider(ContextProvider $provider): QueryHandlerInterface;
}
