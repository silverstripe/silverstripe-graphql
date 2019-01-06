<?php

namespace SilverStripe\GraphQL\Storage\Encode;

interface ResolverFactoryInterface
{
    /**
     * @param TypeRegistryInterface $registry
     * @return callable
     */
    public function createResolver(TypeRegistryInterface $registry);
}