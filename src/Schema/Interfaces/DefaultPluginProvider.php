<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * For models that can provide a default set of plugins
 */
interface DefaultPluginProvider
{
    /**
     * @return array
     */
    public function getDefaultPlugins(): array;
}
