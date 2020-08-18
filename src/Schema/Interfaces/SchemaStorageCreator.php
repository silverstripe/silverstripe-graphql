<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Schema;

interface SchemaStorageCreator
{
    public function createStore(Schema $schema): SchemaStorageInterface;
}
