<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Schema;

interface SchemaUpdater
{
    public static function updateSchemaOnce(Schema $schema): void;
}
