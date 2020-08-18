<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use GraphQL\Type\Schema as GraphQLSchema;

interface SchemaStorageInterface
{

    /**
     * @return void
     */
    public function persistSchema(): void;

    /**
     * @return GraphQLSchema
     */
    public function getSchema(): GraphQLSchema;

    /**
     * @return void
     */
    public function clear(): void;
}
