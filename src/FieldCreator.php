<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Core\Injector\Injectable;
use GraphQL\Type\Definition\Type;

/**
 * Base type for query types within graphql. I.e. mutations or queries
 *
 * @link https://github.com/webonyx/graphql-php#schema
 *
 * @see MutationCreator
 * @see QueryCreator
 */
class FieldCreator
{
    use Injectable;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @param Manager|null Used to retrieve types (including the one returned from this creator),
     * and nest field types regardless of instantiation of their creators.
     */
    public function __construct(Manager $manager = null)
    {
        $this->manager = $manager;
    }

    /**
     * Returns any fixed attributes for this type. E.g. 'name' or 'description'
     *
     * @link https://github.com/webonyx/graphql-php#schema
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Gets the type for elements within this query, or callback to lazy-load this type
     *
     * @link https://github.com/webonyx/graphql-php#type-system
     * @return Type|callable
     */
    public function type()
    {
        return null;
    }

    /**
     * List of arguments this query accepts.
     *
     * @link https://github.com/webonyx/graphql-php#schema
     * @return array
     */
    public function args()
    {
        return [];
    }

    /**
     * Merge all attributes for this query (type, attributes, resolvers, etc).
     *
     * @return array
     */
    public function getAttributes()
    {
        $args = $this->args();

        $attributes = array_merge([
            'args' => $args,
        ], $this->attributes());

        $type = $this->type();
        if (isset($type)) {
            $attributes['type'] = $type;
        }

        $resolver = $this->getResolver();
        if (isset($resolver)) {
            $attributes['resolve'] = $resolver;
        }

        return $attributes;
    }
    /**
     * Convert the Fluent instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();

        return isset($attributes[$key]) ? $attributes[$key] : null;
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        $attributes = $this->getAttributes();

        return isset($attributes[$key]);
    }

    /**
     * Returns a closure callback to the resolve method. This method
     * will convert an invocation of this operation into a result or set of results.
     *
     * Either implement {@see OperationResolver}, or add a callback resolver within
     * getAttributes() with the 'resolve' key.
     *
     * @link https://github.com/webonyx/graphql-php#query-resolution
     * @see OperationResolver::resolve() for method signature.
     * @return \Closure|null
     */
    protected function getResolver()
    {
        if (! method_exists($this, 'resolve')) {
            return null;
        }

        $resolver = array($this, 'resolve');

        return function () use ($resolver) {
            $args = func_get_args();
            $result = call_user_func_array($resolver, $args);

            return $result;
        };
    }
}
