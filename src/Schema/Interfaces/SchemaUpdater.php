<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Schema;

/**
 * Implementors of this interface can make a one-time, context-free update to the schema,
 * e.g. adding a shared Enum type
 */
interface SchemaUpdater
{
    /**
     * @param Schema $schema
     */
    public static function updateSchema(Schema $schema): void;
}
