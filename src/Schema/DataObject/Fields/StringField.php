<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Fields;


use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBString;

class StringField implements FieldCreator
{
    public function createField(DBField $dbField, ModelField $graphqlField): ModelField
    {
        Schema::invariant(
            $dbField instanceof DBString,
            '%s requires instances of %s. Got %s',
            static::class,
            DBHTMLText::class,
            get_class($dbField)
        );

    }
}
