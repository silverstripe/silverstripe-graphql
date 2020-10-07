<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * Given a name, create a SchemaStorageInterface implementation
 */
interface SchemaStorageCreator
{
    /**
     * @param string $name
     * @return SchemaStorageInterface
     */
    public function createStore(string $name): SchemaStorageInterface;
}
