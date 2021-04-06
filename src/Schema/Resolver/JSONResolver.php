<?php


namespace SilverStripe\GraphQL\Schema\Resolver;

class JSONResolver
{
    /**
     * @param $value
     * @return object
     */
    public static function serialise($value): object
    {
        return (object) $value;
    }

    /**
     * @param $value
     * @return array
     */
    public static function parseValue($value): array
    {
        return (array) $value;
    }

    /**
     * @param $ast
     * @return mixed
     */
    public static function parseLiteral($ast)
    {
        return $ast->value;
    }
}
