<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface ModelDependencyProvider
{
    /**
     * @return array
     */
    public function getModelDependencies(): array;
}
