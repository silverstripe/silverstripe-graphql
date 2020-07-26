<?php


namespace SilverStripe\GraphQL\Schema\Resolver;

use Closure;

class ComposedResolver
{
    public static function create(callable $first, array $callables = []): Closure
    {
        return function (...$params) use ($first, $callables) {
            $obj = array_shift($params);
            $result = $first($obj, ...$params);
            foreach ($callables as $callable) {
                $args = array_merge([$result], $params);
                $result = call_user_func_array($callable, $args);
            }

            return $result;
        };
    }
}
