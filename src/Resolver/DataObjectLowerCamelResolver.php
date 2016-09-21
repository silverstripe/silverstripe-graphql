<?php

namespace Chillu\GraphQL\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\ClassInfo;

/**
 * Infer original field name casing from case insensitive database field comparison.
 * Useful counterpart to {@link \Convert::upperCamelToLowerCamel()}.
 *
 * Caution: Assumes fields have been whitelisted through GraphQL type definitions already.
 * Does not perform any canView() checks or further validation.
 */
class DataObjectLowerCamelResolver implements IResolver {

    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        // Correct case for methods (e.g. canView)
        if($object->hasMethod($info->fieldName)) {
            return $object->{$info->fieldName}();
        }

        // Correct case (and getters)
        if($object->hasField($info->fieldName)) {
            return $object->{$info->fieldName};
        }

        // Infer casing
        if($object instanceof DataObject) {
            $parents = ClassInfo::ancestry($object, true);
            foreach($parents as $parent) {
                $fields = DataObject::database_fields($parent);
                foreach($fields as $fieldName => $fieldClass) {
                    if(strcasecmp($fieldName, $info->fieldName) === 0) {
                        return $object->getField($fieldName);
                    }
                }
            }
        }
    }

}
