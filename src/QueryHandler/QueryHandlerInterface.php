<?php


namespace SilverStripe\GraphQL\QueryHandler;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Schema;
use SilverStripe\GraphQL\Schema\Interfaces\ContextProvider;

/**
 * Query handlers are responsible for applying a query as a string to a Schema object
 * and returning a result.
 */
interface QueryHandlerInterface
{
    /**
     * @param Schema $schema
     * @param string|DocumentNode $query
     * @param array $params
     * @return array
     */
    public function query(Schema $schema, $query, array $params = []): array;

    /**
     * Serialise a Graphql result object for output
     *
     * @param ExecutionResult $executionResult
     * @return array
     */
    public function serialiseResult(ExecutionResult $executionResult): array;

    /**
     * @param ContextProvider $provider
     * @return $this
     */
    public function addContextProvider(ContextProvider $provider): QueryHandlerInterface;
}
