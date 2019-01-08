<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;

class RegistryFetcher implements NamedTypeFetcherInterface
{
    /**
     * @var BuilderFactory
     */
    protected $factory;

    /**
     * RegistryFetcher constructor.
     * @param BuilderFactory $factory
     */
    public function __construct(BuilderFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param string $type
     * @return Expr
     */
    public function getExpression($type)
    {
        return $this->factory->methodCall(
            $this->factory->var('this'),
            'getType',
            [
                Helpers::normaliseValue($type),
            ]
        );
    }
}