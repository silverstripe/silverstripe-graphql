<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


use SilverStripe\GraphQL\Storage\Encode\ClosureFactoryInterface;
use Closure;
use SilverStripe\GraphQL\Storage\Encode\RegistryAwareClosureFactory;
use SilverStripe\GraphQL\Storage\Encode\TypeRegistryInterface;

class RegistryResolverAbstraction extends ResolverAbstraction
{
    /**
     * @var ClosureFactoryInterface
     */
    protected $factory;

    /**
     * DynamicResolverAbstraction constructor.
     * @param RegistryAwareClosureFactory $factory
     */
    public function __construct(RegistryAwareClosureFactory $factory)
    {
        $this->setFactory($factory);
    }

    /**
     * @param RegistryAwareClosureFactory $factory
     * @return $this
     */
    public function setFactory(RegistryAwareClosureFactory $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * @return Closure
     */
    public function export()
    {
        return $this->factory;
    }

}