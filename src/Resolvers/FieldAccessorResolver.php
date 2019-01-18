<?php

namespace SilverStripe\GraphQL\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Scaffolding\Interfaces\StaticResolverInterface;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;

class FieldAccessorResolver implements StaticResolverInterface
{
    public static function resolve($obj, array $args, $context, ResolveInfo $info)
    {
        /**
         * @var DataObject $obj
         */
        $field = $obj->obj($info->fieldName);
        // return the raw field value, or checks like `is_numeric()` fail
        if ($field instanceof DBField && $field->isInternalGraphQLType()) {
            return $field->getValue();
        }
        return $field;
    }
}
