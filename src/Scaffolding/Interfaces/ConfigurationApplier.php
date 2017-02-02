<?php

namespace SilverStripe\GraphQL\Scaffolding\Interfaces;

/**
 * Defines the methods required for a class to accept a configuration as an array
 */
interface ConfigurationApplier
{
    /**
     * @param  array  $config
     */
    public function applyConfig(array $config);
}
