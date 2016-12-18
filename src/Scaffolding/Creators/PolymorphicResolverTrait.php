<?php

namespace SilverStripe\GraphQL\Scaffolding\Creators;

use SilverStripe\GraphQL\Scaffolding\ResolverInterface;

trait PolymorphicResolverTrait
{
    /**
     * @var \Closure|SilverStripe\GraphQL\ResolverInterface
     */
    protected $resolver;

    protected function createResolverFunction()
    {
        $resolver = $this->resolver;

        return function () use ($resolver) {
            $args = func_get_args();
            if (is_callable($resolver)) {
                return call_user_func_array($resolver, $args);
            } else {
                if ($resolver instanceof ResolverInterface) {
                    return call_user_func_array([$resolver, 'resolve'], $args);
                } else {
                    throw new \Exception(sprintf(
                        '%s resolver must be a closure or implement %s',
                        __CLASS__,
                        ResolverInterface::class
                    ));
                }
            }
        };
    }
}