<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use Closure;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryInterface;

interface RegistryAwareClosureFactoryInterface
{
    /**
     * @param TypeRegistryInterface $registry
     * @return Closure
     */
    public function createClosure(TypeRegistryInterface $registry);
}