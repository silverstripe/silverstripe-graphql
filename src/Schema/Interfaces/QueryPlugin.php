<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * A plugin that is used for a generic query
 */
interface QueryPlugin extends PluginInterface
{
    /**
     * @param Query $query
     * @param Schema $schema
     * @param array $config
     */
    public function apply(Query $query, Schema $schema, array $config = []): void;
}
