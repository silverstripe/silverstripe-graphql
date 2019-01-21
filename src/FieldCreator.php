<?php

namespace SilverStripe\GraphQL;

use GraphQL\Error\Error;
use SilverStripe\Core\Injector\Injectable;
use GraphQL\Type\Definition\Type;
use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\AbstractFunction;
use SilverStripe\GraphQL\Schema\Components\StaticFunction;
use SilverStripe\GraphQL\Schema\Components\TypeReference;

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
     * @return TypeReference
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
     * If the field creates types dynamically, use this hook to add them
     * to the manager
     * @return array
     */
    public function extraTypes()
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
     * @deprecated 4.0 Use toField() instead
     * @throws Error
     */
    public function toArray()
    {
        Deprecation::notice('4.0', 'Please use toField() instead');
        return $this->toField();
    }

    /**
     * @return \SilverStripe\GraphQL\Schema\Components\Field
     */
    public function toField()
    {
        return Field::create(
            $this->name,
            $this->type(),
            $this->getResolver(),
            $this->args()
        );
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
     * @return StaticFunction
     */
    protected function getResolver()
    {
        $callable = [static::class, 'resolve'];
        if (!is_callable($callable)) {
            return null;
        }

        return new StaticFunction($callable);
    }
}
