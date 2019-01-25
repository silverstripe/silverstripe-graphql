<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

/**
 * Provides a lookup of types by arbitrary but unique string identifiers.
 * Can be used for lazy loading of types during schema construction.
 */
interface TypeRegistryInterface
{
    /**
     * @param string $name
     * @return mixed
     */
    public function getType($name);

    /**
     * @param $name
     * @return bool
     */
    public function hasType($name);
}
