<?php


namespace SilverStripe\GraphQL\Schema\Resolver;

use Closure;

/**
 * Given a stack of resolver middleware and afterware, compress it into one composed function,
 * passing along the return value.
 */
class ComposedResolver
{
    /**
     * @todo This could probably just accept one [array $callables] parameter.
     *
     * @param callable $resolver
     * @param array $before
     * @param array $after
     * @return Closure
     */
    public static function create(callable $resolver, array $before = [], array $after = []): Closure
    {
        return function (...$params) use ($resolver, $before, $after) {
            $isDone = false;
            $done = function () use (&$isDone) {
                $isDone = true;
            };
            $params[] = $done;
            $obj = array_shift($params);
            $callables = array_merge($before, [$resolver], $after);
            $first = array_shift($callables);
            $result = $first($obj, ...$params);
            foreach ($callables as $callable) {
                if ($isDone) {
                    return $result;
                }
                $args = array_merge([$result], $params);
                $result = call_user_func_array($callable, $args);
            }

            return $result;
        };
    }
}
