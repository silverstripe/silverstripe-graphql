<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;

/**
 * Generic resolver for DataObjects
 */
class Resolver
{
    /**
     * @param DataObject $obj
     * @param array $args
     * @param array $context
     * @param ResolveInfo|null $info
     * @return array|bool|int|mixed|DataList|DataObject|DBField|SS_List|string|null
     * @throws SchemaBuilderException
     */
    public static function resolve($obj, $args = [], $context = [], ?ResolveInfo $info = null)
    {
        $fieldName = $info->fieldName;
        $context = SchemaConfigProvider::get($context);
        $class = get_class($obj);
        $resolvedField = null;
        while (!$resolvedField && $class !== DataObject::class) {
            $resolvedField = $context->mapFieldByClassName($class, $fieldName);
            $class = get_parent_class($class);
        }

        if (!$resolvedField) {
            return null;
        }
        $result = FieldAccessor::singleton()->accessField($obj, $resolvedField[1]);
        if ($result instanceof DBField) {
            return $result->getValue();
        }

        return $result;
    }

    /**
     * Just the basic ViewableData field accessor bit, without all the property mapping
     * overhead. Useful for custom dataobject types that circumvent the model layer.
     *
     * @param DataObject $obj
     * @param array $args
     * @param array $context
     * @param ResolveInfo|null $info
     * @return array|bool|int|mixed|DataList|DataObject|DBField|SS_List|string|null
     */
    public static function baseResolve($obj, $args = [], $context = [], ?ResolveInfo $info = null)
    {
        $fieldName = $info->fieldName;
        $result = FieldAccessor::singleton()->accessField($obj, $fieldName);
        if ($result instanceof DBField) {
            return $result->getValue();
        }

        return $result;
    }
}
