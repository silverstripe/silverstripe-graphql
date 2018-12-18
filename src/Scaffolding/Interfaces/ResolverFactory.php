<?php

namespace SilverStripe\GraphQL\Scaffolding\Interfaces;

use Closure;

interface ResolverFactory
{
    /**
     * @return Closure
     */
    public function createResolver();
}