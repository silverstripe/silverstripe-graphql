<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Schema;
use GraphQL\Type\Schema as GraphQLSchema;

interface SchemaStorageInterface
{

    /**
     * @param Schema $schema
     */
    public function persistSchema(Schema $schema): void;

    /**
     * @param string $key
     * @return GraphQLSchema
     */
    public function getSchema(string $key): GraphQLSchema;
}
