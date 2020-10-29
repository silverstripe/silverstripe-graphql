<?php


namespace SilverStripe\GraphQL\Schema\Field;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\FieldPlugin;
use SilverStripe\GraphQL\Schema\Interfaces\PluginValidator;
use SilverStripe\GraphQL\Schema\Interfaces\QueryPlugin;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * Defines a generic query
 */
class Query extends Field implements PluginValidator
{
    /**
     * @param string $pluginName
     * @param $plugin
     * @throws SchemaBuilderException
     */
    public function validatePlugin(string $pluginName, $plugin): void
    {
        Schema::invariant(
            $plugin && ($plugin instanceof QueryPlugin || $plugin instanceof FieldPlugin),
            'Plugin %s not found or does not apply to query "%s"',
            $pluginName,
            $this->getName()
        );
    }
}
