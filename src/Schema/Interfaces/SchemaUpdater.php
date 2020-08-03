<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Schema;

interface SchemaUpdater
{
    public static function updateSchema(Schema $schema): void;
}
