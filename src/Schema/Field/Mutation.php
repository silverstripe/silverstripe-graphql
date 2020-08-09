<?php


namespace SilverStripe\GraphQL\Schema\Field;


use SilverStripe\GraphQL\Schema\Interfaces\FieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\MutationPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\PluginValidator;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * Defines a generic mutation
 */
class Mutation extends Field implements PluginValidator
{
    public function validatePlugin(string $pluginName, $plugin): void
    {
        Schema::invariant(
            $plugin && ($plugin instanceof MutationPlugin || $plugin instanceof FieldPlugin),
            'Plugin %s not found or not an instance of %s',
            $pluginName,
            MutationPlugin::class
        );
    }
}
