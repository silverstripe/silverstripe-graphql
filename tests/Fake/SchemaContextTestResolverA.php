<?php

namespace SilverStripe\GraphQL\Tests\Fake;

class SchemaContextTestResolverA
{
    public static function resolveTypeNameFieldName()
    {
        return __FUNCTION__;
    }

    public static function resolveTypeName()
    {
        return __FUNCTION__;
    }

    public static function resolve()
    {
        return __FUNCTION__;
    }

}
