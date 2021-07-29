<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Type\Enum;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Defines a set of arguments that applies to a field that maps to a DBField.
 * Provides an Enum of options, and a resolver
 */
abstract class DBFieldArgs
{
    use Injectable;

    /**
     * @return Enum
     */
    abstract public function getEnum(): Enum;

    /**
     * @param ModelField $field
     */
    abstract public function applyToField(ModelField $field): void;

    /**
     * @param DBField $obj
     * @param array $args
     * @return mixed
     */
    public static function baseFormatResolver(DBField $obj, array $args)
    {
        $format = $args['format'] ?? null;
        if ($format) {
            if ($obj->hasMethod($format)) {
                return $obj->obj($format);
            }
        }

        return $obj;
    }
}
