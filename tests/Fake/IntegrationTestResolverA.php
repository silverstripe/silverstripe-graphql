<?php

namespace SilverStripe\GraphQL\Tests\Fake;

class IntegrationTestResolverA
{
    public static function resolveReadMyTypes($obj, $args = [])
    {
        return [
            ['field1' => 'foo', 'field2' => 2],
            ['field1' => 'bar', 'field2' => 3],
        ];
    }

    public static function resolveReadMyTypesAgain()
    {
        return [
            ['field1' => 'foo', 'field2' => true],
            ['field1' => 'bar', 'field2' => false],
        ];
    }
}
