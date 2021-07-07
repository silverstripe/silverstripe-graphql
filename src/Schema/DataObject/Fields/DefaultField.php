<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Fields;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\ORM\FieldType\DBField;

/**
 * If a DBField doesn't have a custom graphql type it maps to,
 * fall back on this. (sets type to String)
 */
class DefaultField implements FieldCreator
{
    /**
     * @param DBField $dbField
     * @param ModelField $graphqlField
     * @return ModelField
     * @throws SchemaBuilderException
     */
    public function createField(DBField $dbField, ModelField $graphqlField): ModelField
    {
        return $graphqlField->setType('String');
    }
}
