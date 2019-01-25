<?php


namespace SilverStripe\GraphQL\Schema\Components;

use Closure;
use InvalidArgumentException;

/**
 * Provides execution abstraction for a static function
 * which doesn't require any context (e.g. a type registry).
 * See {@link DynamicFunction} for a factory to create a
 * closure instead, or {@link RegistryFunction}
 * for an example on how to pass context into this closure.
 */
class StaticFunction extends AbstractFunction
{
    /**
     * @var callable
     */
    protected $resolver;

    /**
     * StaticFunction constructor.
     * @param $resolver
     */
    public function __construct($resolver)
    {
        $this->setResolver($resolver);
    }

    /**
     * @return callable
     */
    public function export()
    {
        return $this->resolver;
    }

    /**
     * @param callable $resolver
     * @return $this
     */
    public function setResolver($resolver)
    {
        if (!is_callable($resolver) || $resolver instanceof Closure) {
            throw new InvalidArgumentException(sprintf(
                '%s::%s must be callable, but cannot be a closure',
                __CLASS__,
                __FUNCTION__
            ));
        }
        $this->resolver = $resolver;

        return $this;
    }
}
