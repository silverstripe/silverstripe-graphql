<?php


namespace SilverStripe\GraphQL\Schema\Components;

use SilverStripe\GraphQL\Schema\Encoding\Factories\RegistryAwareClosureFactory;

class RegistryFunction extends AbstractFunction
{
    /**
     * @var \SilverStripe\GraphQL\Schema\Encoding\Factories\RegistryAwareClosureFactory
     */
    protected $factory;

    /**
     * DynamicFunction constructor.
     * @param \SilverStripe\GraphQL\Schema\Encoding\Factories\RegistryAwareClosureFactory $factory
     */
    public function __construct(RegistryAwareClosureFactory $factory)
    {
        $this->setFactory($factory);
    }

    /**
     * @param \SilverStripe\GraphQL\Schema\Encoding\Factories\RegistryAwareClosureFactory $factory
     * @return $this
     */
    public function setFactory(RegistryAwareClosureFactory $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * @return \SilverStripe\GraphQL\Schema\Encoding\Factories\RegistryAwareClosureFactory
     */
    public function export()
    {
        return $this->factory;
    }
}
