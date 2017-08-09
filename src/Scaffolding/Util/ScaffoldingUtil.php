<?php

namespace SilverStripe\GraphQL\Scaffolding\Util;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\ViewableData;

class ScaffoldingUtil
{
    /**
     * Given a DataObject subclass name, transform it into a sanitised (and implicitly unique) type
     * name suitable for the GraphQL schema
     *
     * @param  string $class
     * @return string
     */
    public static function typeNameForDataObject($class)
    {
        $typeName = Config::inst()->get($class, 'table_name', Config::UNINHERITED) ?:
            Injector::inst()->get($class)->singular_name();

        return static::typeName($typeName);
    }
    
    public static function typeName($str)
    {
        return preg_replace('/[^A-Za-z0-9_]/', '_', $str);
    }

    /**
     * Returns true if the field name can be accessed on the given object
     *
     * @param  ViewableData $instance
     * @param  $fieldName
     * @return bool
     */
    public static function isValidFieldName(ViewableData $instance, $fieldName)
    {
        return ($instance->hasMethod($fieldName) || $instance->hasField($fieldName));
    }
}
