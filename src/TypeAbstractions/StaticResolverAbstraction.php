<?php


namespace SilverStripe\GraphQL\TypeAbstractions;

use Closure;
use InvalidArgumentException;

class StaticResolverAbstraction extends ResolverAbstraction
{
    /**
     * @var callable
     */
    protected $resolver;

    /**
     * StaticResolverAbstraction constructor.
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