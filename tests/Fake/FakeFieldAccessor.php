<?php


namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\GraphQL\FieldAccessorInterface;
use SilverStripe\View\ViewableData;

class FakeFieldAccessor implements FieldAccessorInterface
{
    public function getObjectFieldName(ViewableData $object, $fieldName, $opts = [])
    {
        $field = strrev($fieldName);

        return $object->hasField($field) ? $field : null;
    }

    public function getValue(ViewableData $object, $fieldName, $opts = [], $asObject = false)
    {
        if ($object->hasField($fieldName)) {
            return $object->obj($fieldName);
        }
        $field = strrev($fieldName);
        return $asObject ? $object->obj($field): $object->$field;
    }

    public function setValue(ViewableData $object, $fieldName, $value, $opts = [])
    {
    }
}
