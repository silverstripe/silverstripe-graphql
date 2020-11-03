<?php


namespace SilverStripe\GraphQL\Util;

use SilverStripe\GraphQL\FieldAccessorInterface;
use SilverStripe\View\ViewableData;

class NaiveFieldAccessor implements FieldAccessorInterface
{
    /**
     * @param ViewableData $object
     * @param $fieldName
     * @param array $opts
     * @param bool $asObject
     * @return mixed|Object|\SilverStripe\ORM\FieldType\DBField
     */
    public function getValue(ViewableData $object, $fieldName, $opts = [], $asObject = false)
    {
        return $asObject ? $object->obj($fieldName) : $object->$fieldName;
    }

    /**
     * @param ViewableData $object
     * @param $fieldName
     * @param $value
     * @param array $opts
     * @return mixed|void
     */
    public function setValue(ViewableData $object, $fieldName, $value, $opts = [])
    {
        $object->$fieldName = $value;
    }

    /**
     * @param ViewableData $object
     * @param $fieldName
     * @param array $opts
     * @return string|null
     */
    public function getObjectFieldName(ViewableData $object, $fieldName, $opts = [])
    {
        if ($object->hasField($fieldName) || $object->hasMethod($fieldName)) {
            return $fieldName;
        }

        return null;
    }
}
