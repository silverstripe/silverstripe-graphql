<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use GraphQL\Type\Schema as GraphQLSchema;
use SilverStripe\GraphQL\Schema\Schema;

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
     * @return void
     */
    public function clear(): void;
}
