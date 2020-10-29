<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * A plugin that applies to a Field abstraction
 */
interface FieldPlugin extends PluginInterface
{
    /**
     * @param Field $field
     * @param Schema $schema
     * @param array $config
     */
    public function apply(Field $field, Schema $schema, array $config = []): void;
}
