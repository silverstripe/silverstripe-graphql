<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface DefaultPluginProvider
{
    /**
     * @return array
     */
    public function getDefaultPlugins(): array;
}
