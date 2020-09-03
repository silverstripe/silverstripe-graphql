<?php


namespace SilverStripe\GraphQL\Schema\DataObject;


use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use Closure;

/**
 * Generic resolver for DataObjects
 */
class Resolver
{
    /**
     * @param DataObject $obj
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return string|bool|int|float|null
     */
    public static function resolve(DataObject $obj, array $args, array $context, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        return static::resolveField($obj, $fieldName);
    }

    /**
     * Property mapping allows custom property names for a DataObject field rather
     * than relying on basic field formatting
     *
     * @param array $resolverContext
     * @return Closure
     */
    public static function resolveContext(array $resolverContext = []): Closure
    {
        $propertyMapping = $resolverContext['propertyMapping'];
        return function(
            DataObject $obj,
            array $args,
            array $context,
            ResolveInfo $info
        ) use ($propertyMapping) {
            $fieldName = $info->fieldName;
            $property = $propertyMapping[$fieldName] ?? null;
            if (!$property) {
                return null;
            }
            return static::resolveField($obj, $property);
        };
    }

    /**
     * @param DataObject $obj
     * @param string $fieldName
     * @return string|bool|int|float|null
     */
    protected static function resolveField(DataObject $obj, string $fieldName)
    {
        $result = FieldAccessor::singleton()->accessField($obj, $fieldName);
        if ($result instanceof DBField) {
            return $result->getValue();
        }

        return $result;
    }
}
