<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


use SilverStripe\GraphQL\Storage\Encode\ClosureFactoryInterface;
use Closure;

class DynamicResolverAbstraction extends ResolverAbstraction
{
    /**
     * @var ClosureFactoryInterface
     */
    protected $factory;

    /**
     * DynamicResolverAbstraction constructor.
     * @param ClosureFactoryInterface $factory
     */
    public function __construct(ClosureFactoryInterface $factory)
    {
        $this->setFactory($factory);
    }

    /**
     * @param ClosureFactoryInterface $factory
     * @return $this
     */
    public function setFactory(ClosureFactoryInterface $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * @return callable|Closure
     */
    public function export()
    {
        return $this->factory;
    }

}