<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Field\Mutation;
use SilverStripe\GraphQL\Schema\Schema;

interface MutationPlugin extends PluginInterface
{
    /**
     * @param Mutation $query
     * @param Schema $schema
     * @param array $config
     */
    public function apply(Mutation $query, Schema $schema, array $config = []): void;
}
