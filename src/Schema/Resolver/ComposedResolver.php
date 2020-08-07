<?php


namespace SilverStripe\GraphQL\Schema\Resolver;

use Closure;

class ComposedResolver
{
    /**
     * @param callable $resolver
     * @param array $before
     * @param array $after
     * @return Closure
     */
    public static function create(callable $resolver, array $before = [], array $after = []): Closure
    {
        return function (...$params) use ($resolver, $before, $after) {
            $obj = array_shift($params);
            $callables = array_merge($before, [$resolver], $after);
            $first = array_shift($callables);
            $result = $first($obj, ...$params);
            foreach ($callables as $callable) {
                $args = array_merge([$result], $params);
                $result = call_user_func_array($callable, $args);
            }

            return $result;
        };
    }
}
