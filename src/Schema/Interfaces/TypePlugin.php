<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Type;

/**
 * A plugin that applies to a generic Type
 */
interface TypePlugin extends PluginInterface
{
    /**
     * @param Type $type
     * @param Schema $schema
     * @param array $config
     */
    public function apply(Type $type, Schema $schema, array $config = []): void;
}
