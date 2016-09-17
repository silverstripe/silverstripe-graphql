<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Core\Object;
use GraphQL\Type\Definition\ObjectType;

/**
 * Represents a GraphQL type in a way that allows customisation
 * through SilverStripe's DataExtension system.
 */
class TypeCreator extends Object
{
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
     * Returns field structure with field resolvers added.
     *
     * @return array
     */
    public function getFields()
    {
        $fields = $this->fields();

        // TODO Field resolution

        return $fields;
    }

    /**
     * @return ObjectType
     */
    public function toType()
    {
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
        return array_merge(
            $this->attributes(),
            [
                'fields' => function () {
                    return $this->getFields();
                },
            ]
        );
    }
}
