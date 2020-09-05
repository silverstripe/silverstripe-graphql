<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * Implemented by classes that can provide default plugins to nested queries
 */
interface NestedDefaultPluginProvider
{
    /**
     * @return array
     */
    public function getNestedDefaultPlugins(): array;
}
