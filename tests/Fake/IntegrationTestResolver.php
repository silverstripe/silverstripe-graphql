<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\GraphQL\Schema\Plugin\PaginationPlugin;

class IntegrationTestResolver
{
    public static function resolveReadMyTypes($obj, $args = [])
    {
        return [
            ['field1' => 'foo', 'field2' => 2, 'field3' => 3],
            ['field1' => 'bar', 'field2' => 3, 'field3' => 3],
        ];
    }

    public static function resolveMyTypeField3($obj, $args = [])
    {
        $arg = $args['MyArg'] ?? null;
        if ($arg) {
            return 'arg';
        }

        return 'no arg';
    }

    public static function resolveReadMyTypesAgain()
    {
        return [
            ['field1' => 'foo', 'field2' => true],
            ['field1' => 'bar', 'field2' => false],
        ];
    }

    public static function lotsOfMyTypes($obj, $args = [])
    {
        return array_map(function ($num) {
            return ['field1' => 'field1-' . $num];
        }, range(1, 100));
    }

    public static function testPaginate($maxLimit)
    {
        return function ($list, $args) use ($maxLimit) {
            $limit = min(($args['limit'] ?? 10), $maxLimit);
            $offset = $args['offset'] ?? 0;
            return PaginationPlugin::createPaginationResult(
                count($list ?? []),
                array_slice($list ?? [], $offset ?? 0, $limit),
                $limit,
                $offset
            );
        };
    }
}
