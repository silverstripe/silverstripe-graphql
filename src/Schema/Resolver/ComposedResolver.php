<?php


namespace SilverStripe\GraphQL\Schema\Resolver;

use Closure;
use Exception;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\ResolverFailure;

/**
 * Given a stack of resolver middleware and afterware, compress it into one composed function,
 * passing along the return value.
 */
class ComposedResolver
{
    use Injectable;

    /**
     * @var callable[]
     */
    private $resolvers;

    /**
     * @param callable[] $resolvers
     * @return Closure
     */
    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    public function toClosure(): Closure
    {
        return function (...$params) {
            $isDone = false;
            $done = function () use (&$isDone) {
                $isDone = true;
            };
            $params[] = $done;
            $obj = array_shift($params);
            $callables = $this->resolvers;
            $first = array_shift($callables);
            $result = $first($obj, ...$params);
            foreach ($callables as $callable) {
                if ($isDone) {
                    return $result;
                }
                $args = array_merge([$result], $params);
                try {
                    $result = call_user_func_array($callable, $args);
                } catch (Exception $e) {
                    throw new ResolverFailure(
                        $callable,
                        $args,
                        $e->getMessage()
                    );
                }
            }

            return $result;
        };
    }
}
