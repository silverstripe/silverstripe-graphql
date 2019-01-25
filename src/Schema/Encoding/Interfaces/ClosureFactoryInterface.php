<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use Closure;

/**
 * Anonymous functions are not encodable, for obvious reasons,
 * so for attributes of types that must be callable (e.g. resolvers)
 * but cannot be declared statically, these implementations provide a createClosure() method
 * which returns the function just-in-time as part of the creation of the type in the registry.
 * A the time createClosure() is executed, it has access to any context the scaffolders have provided to it.
 */
interface ClosureFactoryInterface
{
    /**
     * @return Closure
     */
    public function createClosure();

    /**
     * @return array
     */
    public function getContext();
}
