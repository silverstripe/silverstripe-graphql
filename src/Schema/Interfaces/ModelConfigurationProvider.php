<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Config\ModelConfiguration;

interface ModelConfigurationProvider
{
    /**
     * @return ModelConfiguration|null
     */
    public function getModelConfig(): ?ModelConfiguration;

    /**
     * @param ModelConfiguration $config
     */
    public function applyModelConfig(ModelConfiguration $config): void;
}
