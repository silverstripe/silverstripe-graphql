<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use GraphQL\Type\Schema as GraphQLSchema;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\SchemaContext;
use SilverStripe\GraphQL\Schema\StorableSchema;

/**
 * Persists a graphql-php Schema object, and retrieves it
 */
interface SchemaStorageInterface
{

    /**
     * @param StorableSchema $schema
     * @return void
     */
    public function persistSchema(StorableSchema $schema): void;

    /**
     * @return GraphQLSchema
     * @throws SchemaNotFoundException
     */
    public function getSchema(): GraphQLSchema;

    /**
     * @return SchemaContext
     */
    public function getContext(): SchemaContext;

    /**
     * @return void
     */
    public function clear(): void;

    /**
     * @return bool
     */
    public function exists(): bool;
}
