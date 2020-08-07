<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Field\ModelQuery;
use SilverStripe\GraphQL\Schema\Schema;

interface ModelQueryPlugin extends PluginInterface
{
    public function apply(ModelQuery $query, Schema $schema, array $config = []): void;
}
