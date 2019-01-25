<?php


namespace SilverStripe\GraphQL\Schema\Components;

use SilverStripe\GraphQL\Schema\Encoding\Factories\RegistryAwareClosureFactory;

/**
 * Encapsulates a {@link RegistryAwareClosureFactory}
 * which can generate a closure function that closes over
 * required execution context (the registry).
 *
 * See {@link \SilverStripe\GraphQL\Schema\Encoding\Encoders\RegistryFunctionEncoder}
 * on how this function is encoded.
 *
 * See {@link AbstractFunction} for rationale why this
 * abstraction level if required.
 */
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
