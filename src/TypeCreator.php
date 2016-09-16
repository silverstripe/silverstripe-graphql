<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Core\Object;

/**
 * Represents a GraphQL type in a way that allows customisation
 * through SilverStripe's DataExtension system.
 */
class TypeCreator extends Object {

    /**
     * Returns the internal field structures, without field resolution.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }

}
