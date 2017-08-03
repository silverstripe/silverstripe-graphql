<?php

namespace SilverStripe\GraphQL\Scaffolding\Interfaces;

/**
 * Defines the interface used for services that create GraphQL types
 * based on simple input, e.g. a formatted string or array
 */
interface TypeParserInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return \GraphQL\Type\Definition\Type
     */
    public function getType();
}
