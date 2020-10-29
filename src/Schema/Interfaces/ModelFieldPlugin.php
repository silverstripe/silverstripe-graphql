<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Schema;

interface ModelFieldPlugin extends PluginInterface
{
    /**
     * @param ModelField $field
     * @param Schema $schema
     * @param array $config
     */
    public function apply(ModelField $field, Schema $schema, array $config = []): void;
}
