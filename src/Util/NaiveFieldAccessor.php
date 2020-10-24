<?php


namespace SilverStripe\GraphQL\Util;

use SilverStripe\GraphQL\FieldAccessorInterface;
use SilverStripe\View\ViewableData;

class NaiveFieldAccessor implements FieldAccessorInterface
{
    public function getValue(ViewableData $object, $fieldName, $opts = [], $asObject = false)
    {
        return $object->obj($fieldName);
    }

    public function setValue(ViewableData $object, $fieldName, $value, $opts = [])
    {
        $object->$fieldName = $alue;
    }

    public function getObjectFieldName(ViewableData $object, $fieldName, $opts = [])
    {
        return $fieldName;
    }
}
