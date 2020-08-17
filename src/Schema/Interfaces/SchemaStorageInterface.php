<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Schema;

interface SchemaStorageInterface
{

    /**
     * @param Schema $schema
     */
    public function persistSchema(Schema $schema): void;

    /**
     * @param Schema $schema
     */
    public function loadRegistry(Schema $schema): void;

    /**
     * @param string $key
     * @return string
     */
    public function getRegistryClassName(string $key): string;
}
