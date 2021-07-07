<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Fields;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\ORM\FieldType\DBField;

class IntField implements FieldCreator
{
    /**
     * @param DBField $dbField
     * @param ModelField $graphqlField
     * @return ModelField
     * @throws SchemaBuilderException
     */
    public function createField(DBField $dbField, ModelField $graphqlField): ModelField
    {
        return $graphqlField->setType('Int');
    }
}
