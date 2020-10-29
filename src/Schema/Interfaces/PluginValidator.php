<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;

/**
 * Validates that a given plugin is acceptable for the implementing class
 */
interface PluginValidator
{
    /**
     * @param string $pluginName
     * @param $plugin
     * @throws SchemaBuilderException
     */
    public function validatePlugin(string $pluginName, $plugin): void;
}
