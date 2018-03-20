<?php

namespace SilverStripe\GraphQL\Scaffolding\Util;

use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\View\ViewableData;

/**
 * @deprecated 2.0...3.0 use StaticSchema instead
 */
class ScaffoldingUtil
{
    /**
     * @deprecated 2.0..3.0 Use StaticSchema::inst()->typeNameForDataObject() instead
     * @param string $class
     * @return string
     */
    public static function typeNameForDataObject($class)
    {
        Deprecation::notice('3.0', 'Use StaticSchema::inst()->typeNameForDataObject() instead');
        return StaticSchema::inst()->typeNameForDataObject($class);
    }

    /**
     * @deprecated 2.0..3.0 Use StaticSchema::inst()->typeName() instead
     * @param string $str
     * @return string
     */
    public static function typeName($str)
    {
        Deprecation::notice('3.0', 'Use StaticSchema::inst()->typeName() instead');
        return StaticSchema::inst()->typeName($str);
    }

    /**
     * @deprecated 2.0..3.0 Use StaticSchema::inst()->isValidFieldName() instead
     * @param ViewableData $instance
     * @param string $fieldName
     * @return bool
     */
    public static function isValidFieldName(ViewableData $instance, $fieldName)
    {
        Deprecation::notice('3.0', 'Use StaticSchema::inst()->isValidFieldName() instead');
        return StaticSchema::inst()->isValidFieldName($instance, $fieldName);
    }
}
