<?php

namespace SilverStripe\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\Core\Injector\Injectable;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Represents a GraphQL type in a way that allows customization through
 * SilverStripe's {@link DataExtension} system.
 *
 * @link https://github.com/webonyx/graphql-php#type-system
 */
class TypeCreator
{
    use Injectable;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * Determines if the object should be cast as an {@link InputObjectType}
     * Otherwise will be cast as a normal {@link ObjectType}
     *
     * @var bool
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
     * Returns any fixed attributes for this type. E.g. 'name' or 'description'
     *
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
     * Returns the list of interfaces (or function to evaluate this list)
     * which this type implements.
     *
     * @return array|callable
     */
    public function interfaces()
    {
        return [];
    }

    /**
     * Returns field structure with field resolvers added.
     * Note that to declare a field resolver for a particular field,
     * create a resolve<Name>Field() method to your subclass.
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
     * True if this is an input object, which accepts new field values.
     *
     * @return bool
     */
    public function isInputObject()
    {
        return $this->inputObject;
    }

    /**
     * Build the constructed type backing this object.
     *
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
     * Convert this silverstripe graphql type into an array format accepted by the
     * type constructor.
     *
     * @see InterfaceType::__construct
     * @see ObjectType::__construct
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Gets the list of all computed attributes for this type.
     *
     * @return array
     */
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

        if (!empty($interfaces)) {
            $attributes['interfaces'] = $interfaces;
        }

        return $attributes;
    }

    /**
     * Locate potential callback for resolving this field at runtime.
     * E.g. A callback for retrieving the list of child files for a folder
     * Will automatically inspect itself for methods named either resolve<Name>Field(),
     * or resolveField().
     *
     * @param string $name Name of the field
     * @param array $field Field array specification
     * @return callable|null The callback, or null if there is no field resolver
     */
    protected function getFieldResolver($name, $field)
    {
        // Preconfigured method
        if (isset($field['resolve'])) {
            return $field['resolve'];
        }
        $candidateMethods = [
            'resolve'.ucfirst($name).'Field',
            'resolveField',
        ];
        foreach ($candidateMethods as $resolveMethod) {
            if (!method_exists($this, $resolveMethod)) {
                continue;
            }

            // Method for a particular field
            $resolver = array($this, $resolveMethod);
            return function () use ($resolver) {
                $args = func_get_args();
                // See 'resolveType' on https://github.com/webonyx/graphql-php
                return call_user_func_array($resolver, $args);
            };
        }

        return null;
    }
}
