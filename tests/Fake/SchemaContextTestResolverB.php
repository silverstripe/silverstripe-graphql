<?php


namespace SilverStripe\GraphQL\Tests\Fake;

class SchemaContextTestResolverB
{
    public static function resolveSpecialField()
    {
        return __FUNCTION__;
    }
}
