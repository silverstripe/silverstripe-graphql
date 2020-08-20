<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

interface SchemaStorageCreator
{
    /**
     * @param string $name
     * @return SchemaStorageInterface
     */
    public function createStore(string $name): SchemaStorageInterface;
}
