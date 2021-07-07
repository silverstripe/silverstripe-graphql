<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Fields;


use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Defines a service that maps a DBField instance to a GraphQL field component.
 * Used for adding custom args and/or resolvers for specific DBField types, e.g.
 * DBDate time receiving a "format" argument with a special resolver that does
 * the formatting.
 */
interface FieldCreator
{
    /**
     * @param DBField $dbField
     * @param ModelField $graphqlField
     * @return ModelField
     */
    public function createField(DBField $dbField, ModelField $graphqlField): ModelField;
}
