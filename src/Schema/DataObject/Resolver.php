<?php


namespace SilverStripe\GraphQL\Schema\DataObject;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\SchemaConfig;
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
        $config = SchemaConfigProvider::get($context);

        return static::getResolvedField($obj, $fieldName, $config);
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

    /**
     * @param $obj
     * @param string $fieldName
     * @param SchemaConfig $config
     * @return mixed|null
     * @throws SchemaBuilderException
     */
    public static function getResolvedField($obj, string $fieldName, SchemaConfig $config)
    {
        $class = get_class($obj);
        $resolvedField = null;
        while (!$resolvedField && $class && $class !== DataObject::class) {
            $resolvedField = $config->mapFieldByClassName($class, $fieldName);
            $class = get_parent_class($class ?? '');
        }

        if (!$resolvedField) {
            return null;
        }

        return FieldAccessor::singleton()->accessField($obj, $resolvedField[1]);
    }
}
