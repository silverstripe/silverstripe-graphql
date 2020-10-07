<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use GraphQL\Type\Schema as GraphQLSchema;
use SilverStripe\GraphQL\Schema\Schema;

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
     */
    public function getSchema(): GraphQLSchema;

    /**
     * @return array
     */
    public function getModelConfiguration(): array;

    /**
     * @param array $config
     */
    public function persistModelConfiguration(array $config): void;

    /**
     * @return void
     */
    public function clear(): void;
}
