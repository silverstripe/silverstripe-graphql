<?php

namespace SilverStripe\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\Core\Object;
use SilverStripe\Core\Injector\Injectable;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Represents a GraphQL type in a way that allows customization through
 * SilverStripe's {@link DataExtension} system.
 */
class TypeCreator
{
    use Injectable;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var bool Determines if the object should be cast as an {@link InputObjectType}
     */
    protected $inputObject = false;

    /**
     * @param Manager|null Used to retrieve types (including the one returned from this creator),
     * and nest field types regardless of instantiation of their creators.
     */
    public function __construct(Manager $manager = null)
    {
        $this->manager = $manager;
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Returns the internal field structures, without field resolution.
     *
     * @return array A map of field names to type instances in the GraphQL\Type\Definition namespace
     */
    public function fields()
    {
        return [];
    }

    /**
     * @return array
     */
    public function interfaces()
    {
        return [];
    }

    /**
     * Returns field structure with field resolvers added.
     *
     * @return array
     */
    public function getFields()
    {
        $fields = $this->fields();
        $allFields = [];

        foreach ($fields as $name => $field) {
            $resolver = $this->getFieldResolver($name, $field);
            if ($resolver) {
                $field['resolve'] = $resolver;
            }
            $allFields[$name] = $field;
        }

        return $allFields;
    }

    /**
     * @return bool
     */
    public function isInputObject()
    {
        return $this->inputObject;
    }

    /**
     * @return Type
     */
    public function toType()
    {
        if ($this->isInputObject()) {
            return new InputObjectType($this->toArray());
        }

        return new ObjectType($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    public function getAttributes()
    {
        $interfaces = $this->interfaces();

        $attributes = array_merge(
            $this->attributes(),
            [
                'fields' => function () {
                    return $this->getFields();
                },
            ]
        );

        if (sizeof($interfaces)) {
            $attributes['interfaces'] = $interfaces;
        }

        return $attributes;
    }

    /**
     * @param $name
     * @param $field
     * @return \Closure|null
     */
    protected function getFieldResolver($name, $field)
    {
        $resolveMethod = 'resolve'.ucfirst($name).'Field';
        if (isset($field['resolve'])) {
            // Preconfigured method
            return $field['resolve'];
        } elseif (method_exists($this, $resolveMethod)) {
            // Method for a particular field
            $resolver = array($this, $resolveMethod);
            return function () use ($resolver) {
                $args = func_get_args();
                return call_user_func_array($resolver, $args);
            };
        } elseif (method_exists($this, 'resolveField')) {
            // Method for all fields
            $resolver = array($this, 'resolveField');
            return function () use ($resolver) {
                $args = func_get_args();
                // See 'resolveType' on https://github.com/webonyx/graphql-php
                return call_user_func_array($resolver, $args);
            };
        }

        return null;
    }
}
