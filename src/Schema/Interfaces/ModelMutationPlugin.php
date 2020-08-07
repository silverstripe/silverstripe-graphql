<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Field\ModelMutation;
use SilverStripe\GraphQL\Schema\Schema;

interface ModelMutationPlugin extends PluginInterface
{
    public function apply(ModelMutation $mutation, Schema $schema, array $config = []): void;

}
