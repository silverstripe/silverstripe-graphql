<?php


namespace SilverStripe\GraphQL\Schema\Field;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\MutationPlugin;
use SilverStripe\GraphQL\Schema\Schema;
use Generator;

class Mutation extends Field
{
    /**
     * @return Generator
     * @throws SchemaBuilderException
     */
    public function loadPlugins(): Generator
    {
        foreach ($this->getPlugins() as $pluginName => $config) {
            $plugin = $this->getPluginRegistry()->getPluginByID($pluginName);
            Schema::invariant(
                $plugin && $plugin instanceof MutationPlugin,
                'Plugin %s not found or not an instance of %s',
                $pluginName,
                MutationPlugin::class
            );
            yield [$plugin, $config];
        }
    }

}
