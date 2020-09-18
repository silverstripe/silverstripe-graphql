<?php


namespace SilverStripe\GraphQL\QueryHandler;


use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;

/**
 * Query handlers are responsible for applying a query as a string to a Schema object
 * and returning a result.
 */
interface QueryHandlerInterface
{
    public function query(Schema $schema, string $query, array $params = []): array;

    /**
     * Serialise a Graphql result object for output
     *
     * @param ExecutionResult $executionResult
     * @return array
     */
    public function serialiseResult(ExecutionResult $executionResult): array;

}
