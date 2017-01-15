<?php

namespace SilverStripe\GraphQL;

use GraphQL\Type\Definition\InterfaceType;

/**
 * Base type creator for interface type generation.
 *
 * @link https://github.com/webonyx/graphql-php#interfaces
 */
class InterfaceTypeCreator extends TypeCreator
{
    /**
     * Returns a callback to the type resolver for this interface
     *
     * @return callable
     */
    protected function getTypeResolver()
    {
        if (!method_exists($this, 'resolveType')) {
            return null;
        }

        $resolver = array($this, 'resolveType');

        return function () use ($resolver) {
            $args = func_get_args();
            return call_user_func_array($resolver, $args);
        };
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        $resolver = $this->getTypeResolver();
        if (isset($resolver)) {
            $attributes['resolveType'] = $resolver;
        }

        return $attributes;
    }

    /**
     * Generates the interface type from its configuration
     *
     * @return InterfaceType
     */
    public function toType()
    {
        return new InterfaceType($this->toArray());
    }
}
