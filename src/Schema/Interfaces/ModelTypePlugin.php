<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;

interface ModelTypePlugin extends PluginInterface
{
    /**
     * @param ModelType $type
     * @param Schema $schema
     * @param array $config
     */
    public function apply(ModelType $type, Schema $schema, array $config = []): void;

}
