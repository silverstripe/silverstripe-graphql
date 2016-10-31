<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Core\Object;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Manager;

class FieldCreator extends Object
{
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

        parent::__construct();
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * @return Type
     */
    public function type()
    {
        return null;
    }

    /**
     * @return array
     */
    public function args()
    {
        return [];
    }

    /**
     * Get the attributes from the container.
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
     */
    public function __isset($key)
    {
        $attributes = $this->getAttributes();

        return isset($attributes[$key]);
    }

    /**
     * @return \Closure|null
     */
    protected function getResolver()
    {
        if (!method_exists($this, 'resolve')) {
            return null;
        }

        $resolver = array($this, 'resolve');

        return function () use ($resolver) {
            $args = func_get_args();

            return call_user_func_array($resolver, $args);
        };
    }
}
