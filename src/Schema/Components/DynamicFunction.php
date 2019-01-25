<?php


namespace SilverStripe\GraphQL\Schema\Components;

use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface;
use Closure;

/**
 * Represents a function which can be created through a {@link ClosureFactoryInterface}.
 * This allows persistence of this function without getting into issues with
 * closures that aren't serialisable. Will not pass in any context into the closure.
 * If that's required, have a look at {@link RegistryFunction} which can pass
 * in the type registry into the closure it creates.
 */
class DynamicFunction extends AbstractFunction
{
    /**
     * @var ClosureFactoryInterface
     */
    protected $factory;

    /**
     * DynamicFunction constructor.
     * @param \SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface $factory
     */
    public function __construct(ClosureFactoryInterface $factory)
    {
        $this->setFactory($factory);
    }

    /**
     * @param \SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface $factory
     * @return $this
     */
    public function setFactory(ClosureFactoryInterface $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * @return ClosureFactoryInterface
     */
    public function export()
    {
        return $this->factory;
    }
}
