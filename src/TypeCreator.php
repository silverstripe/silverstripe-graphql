<?php

namespace SilverStripe\GraphQL;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use LogicException;
use SilverStripe\Core\Injector\Injectable;
use GraphQL\Type\Definition\ObjectType;
use SilverStripe\GraphQL\TypeAbstractions\FieldAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\InputTypeAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\ObjectTypeAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\ResolverAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\StaticResolverAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;

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

        foreach ($fields as $key => $field) {
            $name = null;
            if ($field instanceof FieldAbstraction) {
                $name = $field->getName();
            } else if (is_numeric($key)) {
                if (is_array($field) && isset($field['name'])) {
                    $name = $field['name'];
                } else {
                    throw new LogicException(sprintf(
                        'Enumerated lists of fields must be instances of %s or an array that contains a "name" key',
                        FieldAbstraction::class
                    ));
                }
            } else {
                $name = $key;
            }
            $resolver = $this->getFieldResolver($name, $field);
            if ($resolver) {
                $field->setResolver($resolver);
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

    public function getName()
    {
        $attrs = $this->attributes();

        return isset($attrs['name']) ? $attrs['name'] : null;
    }

    public function getDescription()
    {
        $attrs = $this->attributes();

        return isset($attrs['description']) ? $attrs['description'] : null;
    }

    /**
     * Build the constructed type backing this object.
     *
     * @return TypeAbstraction
     */
    public function toType()
    {
        if ($this->isInputObject()) {
            return new InputTypeAbstraction(
                $this->getName(),
                $this->getDescription(),
                $this->getFields()
            );
        }

        return new ObjectTypeAbstraction(
            $this->getName(),
            $this->getDescription(),
            $this->getFields(),
            $this->interfaces()
        );
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
                'fields' => $this->getFields(),
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
     * @param FieldAbstraction $field Field array specification
     * @return ResolverAbstraction
     */
    protected function getFieldResolver($name, FieldAbstraction $field)
    {
        // Preconfigured method
        if ($field->getResolver()) {
            return $field->getResolver();
        }
        $candidateMethods = [
            'resolve'.ucfirst($name).'Field',
            'resolveField',
        ];
        foreach ($candidateMethods as $resolveMethod) {
            $callable = [static::class, $resolveMethod];
            if (is_callable($callable)) {
                return new StaticResolverAbstraction($callable);
            }
        }

        return null;
    }
}
