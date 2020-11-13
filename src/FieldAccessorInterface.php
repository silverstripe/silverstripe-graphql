<?php


namespace SilverStripe\GraphQL;

use SilverStripe\View\ViewableData;

interface FieldAccessorInterface
{
    /**
     * @param ViewableData $object
     * @param $fieldName
     * @param array $opts
     * @param bool $asObject
     * @return mixed
     */
    public function getValue(ViewableData $object, $fieldName, $opts = [], $asObject = false);

    /**
     * @param ViewableData $object
     * @param $fieldName
     * @param $value
     * @param array $opts
     * @return mixed
     */
    public function setValue(ViewableData $object, $fieldName, $value, $opts = []);

    /**
     * @param ViewableData $object
     * @param $fieldName
     * @param array $opts
     * @return string|null
     */
    public function getObjectFieldName(ViewableData $object, $fieldName, $opts = []);
}
