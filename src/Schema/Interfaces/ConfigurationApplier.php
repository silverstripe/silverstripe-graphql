<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * Applies an array of config to a class
 */
interface ConfigurationApplier
{
    /**
     * @param array $config
     * @return mixed
     */
    public function applyConfig(array $config);
}
