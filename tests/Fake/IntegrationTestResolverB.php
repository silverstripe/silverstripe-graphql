<?php


namespace SilverStripe\GraphQL\Tests\Fake;


class IntegrationTestResolverB
{
    public static function resolveSpecialField()
    {
        return __FUNCTION__;
    }
}
