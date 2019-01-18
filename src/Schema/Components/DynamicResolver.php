<?php


namespace SilverStripe\GraphQL\Schema\Components;

use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface;
use Closure;

class DynamicResolver extends AbstractFunction
{
    /**
     * @var ClosureFactoryInterface
     */
    protected $factory;

    /**
     * DynamicResolver constructor.
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
