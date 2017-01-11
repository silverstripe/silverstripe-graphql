<?php

namespace SilverStripe\GraphQL\Scaffolding\Util;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBField;

class ScaffoldingUtil
{
    /**
     * Given a DataObject subclass name, transform it into a sanitised (and implicitly unique) type
     * name suitable for the GraphQL schema
     *
     * @param $class
     * @return mixed
     */
    public static function typeNameForDataObject($class)
    {
        $typeName = Config::inst()->get($class, 'table_name', Config::UNINHERITED) ?:
            Injector::inst()->get($class)->singular_name();

        return preg_replace('/[^A-Za-z0-9_]/', '_', $typeName);
    }


    public static function isValidFieldName(DataObjectInterface $instance, $fieldName)
    {
        return ($instance->hasMethod($fieldName) || $instance->hasField($fieldName));
    }
}
