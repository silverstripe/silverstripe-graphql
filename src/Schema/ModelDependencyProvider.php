<?php


namespace SilverStripe\GraphQL\Schema;


interface ModelDependencyProvider
{
    /**
     * @return array
     */
    public function getModelDependencies(): array;
}
