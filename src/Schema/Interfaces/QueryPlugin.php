<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Schema;

interface QueryPlugin extends PluginInterface
{
    /**
     * @param Query $query
     * @param Schema $schema
     * @param array $config
     */
    public function apply(Query $query, Schema $schema, array $config = []): void;
}
