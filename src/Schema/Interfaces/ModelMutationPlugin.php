<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * A plugin that applies only to mutations created by models
 */
interface ModelMutationPlugin extends PluginInterface
{
    public function apply(ModelMutation $mutation, Schema $schema, array $config = []): void;
}
