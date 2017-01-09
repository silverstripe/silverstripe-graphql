<?php

namespace SilverStripe\GraphQL\Util;

use SilverStripe\ORM\DataObject;
use SilverStripe\Core\ClassInfo;
use SilverStripe\View\ViewableData;
use InvalidArgumentException;

/**
 * Infer original field name casing from case insensitive field comparison.
 * Useful counterpart to {@link \Convert::upperCamelToLowerCamel()}.
 *
 * SilverStripe is using a mix of case sensitive and case insensitive checks,
 * due to the nature of PHP (case sensitive for properties and array keys,
 * case insensitive for methods).
 *
 * Caution: Assumes fields have been whitelisted through GraphQL type definitions already.
 * Does not perform any canView() checks or further validation.
 *
 * @see http://www.php.net/manual/en/functions.user-defined.php
 * @see http://php.net/manual/en/function.array-change-key-case.php
 */
class CaseInsensitiveFieldAccessor
{

    const HAS_METHOD = 'HAS_METHOD';
    const HAS_FIELD = 'HAS_FIELD';
    const HAS_SETTER = 'HAS_SETTER';
    const DATAOBJECT = 'DATAOBJECT';

    /**
     * @param ViewableData $object The parent resolved object
     * @param string $fieldName Name of the field/getter/method
     * @param array $opts Map of which lookups to use (class constants to booleans).
     *              Example: [ViewableDataCaseInsensitiveFieldMapper::HAS_METHOD => true]
     * @return mixed
     */
    public function getValue(ViewableData $object, $fieldName, $opts = [])
    {
        $opts = $opts ?: [];
        $opts = array_merge([
            self::HAS_METHOD => true,
            self::HAS_FIELD => true,
            self::HAS_SETTER => false,
            self::DATAOBJECT => true,
        ], $opts);

        $objectFieldName = $this->getObjectFieldName($object, $fieldName, $opts);

        if (!$objectFieldName) {
            throw new InvalidArgumentException(sprintf(
                'Field name or method "%s" does not exist on %s',
                $fieldName,
                (string)$object
            ));
        }

        // Correct case for methods (e.g. canView)
        if ($object->hasMethod($objectFieldName)) {
            return $object->{$objectFieldName}();
        }

        // Correct case (and getters)
        if ($object->hasField($objectFieldName)) {
            return $object->{$objectFieldName};
        }

        return null;
    }

    /**
     * @param ViewableData $object The parent resolved object
     * @param string $fieldName Name of the field/getter/method
     * @param mixed $value
     * @param array $opts Map of which lookups to use (class constants to booleans).
     *              Example: [ViewableDataCaseInsensitiveFieldMapper::HAS_METHOD => true]
     * @return mixed
     */
    public function setValue(ViewableData $object, $fieldName, $value, $opts = [])
    {
        $opts = $opts ?: [];
        $opts = array_merge([
            self::HAS_METHOD => true,
            self::HAS_FIELD => true,
            self::HAS_SETTER => true,
            self::DATAOBJECT => true,
        ], $opts);

        $objectFieldName = $this->getObjectFieldName($object, $fieldName, $opts);

        if (!$objectFieldName) {
            throw new InvalidArgumentException(sprintf(
                'Field name "%s" does not exist on %s',
                $fieldName,
                (string)$object
            ));
        }

        if ($object->hasMethod($objectFieldName)) {
            // Correct case for methods (e.g. canView)
            $object->{$objectFieldName}($value);
        } elseif ($object->hasField($objectFieldName)) {
            // Correct case (and getters)
            $object->{$objectFieldName} = $value;
        } elseif ($object instanceof DataObject) {
            // Infer casing
            $object->setField($objectFieldName, $value);
        }

        return null;
    }

    /**
     * @param ViewableData $object The object to resolve a name on
     * @param string $fieldName Name in different casing
     * @param array $opts Map of which lookups to use (class constants to booleans).
     *              Example: [ViewableDataCaseInsensitiveFieldMapper::HAS_METHOD => true]
     * @return null|string Name in actual casing on $object
     */
    public function getObjectFieldName(ViewableData $object, $fieldName, $opts = [])
    {
        $opts = $opts ?: [];
        $opts = array_merge([
            self::HAS_METHOD => true,
            self::HAS_FIELD => true,
            self::HAS_SETTER => true,
            self::DATAOBJECT => true,
        ], $opts);

        $optFn = function ($type) use (&$opts) {
            return (in_array($type, $opts) && $opts[$type] === true);
        };

        // Correct case (and getters)
        if ($optFn(self::HAS_FIELD) && $object->hasField($fieldName)) {
            return $fieldName;
        }

        // Infer casing from DataObject fields
        if ($optFn(self::DATAOBJECT) && $object instanceof DataObject) {
            $parents = ClassInfo::ancestry($object, true);
            foreach ($parents as $parent) {
                $fields = DataObject::getSchema()->databaseFields($parent);
                foreach ($fields as $objectFieldName => $fieldClass) {
                    if (strcasecmp($objectFieldName, $fieldName) === 0) {
                        return $objectFieldName;
                    }
                }
            }
        }

        // Setters
        // TODO Support for Object::$extra_methods (case sensitive array key check)
        $setterName = "set" . ucfirst($fieldName);
        if ($optFn(self::HAS_SETTER) && $object->hasMethod($setterName)) {
            return $setterName;
        }

        // Correct case for methods (e.g. canView) - method_exists() is case insensitive
        if ($optFn(self::HAS_METHOD) && $object->hasMethod($fieldName)) {
            return $fieldName;
        }

        return null;
    }
}
