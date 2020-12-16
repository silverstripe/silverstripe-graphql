<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use GraphQL\Type\Schema as GraphQLSchema;
use GraphQL\Type\SchemaConfig;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaContext;

/**
 * Persists a graphql-php Schema object, and retrieves it
 */
interface SchemaStorageInterface
{

    /**
     * @param Schema $schema
     * @return void
     */
    public function persistSchema(Schema $schema): void;

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
}
