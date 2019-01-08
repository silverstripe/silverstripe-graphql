<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use Closure;

interface RegistryAwareClosureFactoryInterface
{
    /**
     * @param TypeRegistryInterface $registry
     * @return Closure
     */
    public function createClosure(TypeRegistryInterface $registry);
}