<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use PhpParser\Node\Expr;
use SilverStripe\Core\Injector\Injectable;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Expr\Variable;

abstract class ResolverFactory implements ResolverFactoryInterface, ExpressionProvider
{
    use Injectable;

    /**
     * @var array
     */
    protected $context = [];

    /**
     * ResolverFactory constructor.
     * @param array $context
     */
    public function __construct($context = [])
    {
        $this->context = $context;
    }

    /**
     * @param TypeRegistryInterface $registry
     * @return callable
     */
    abstract public function createResolver(TypeRegistryInterface $registry);

    /**
     * @return Expr
     */
    public function getExpression()
    {
        return new MethodCall(
            new StaticCall(
                new Name(get_class($this)),
                'create',
                [
                    Helpers::normaliseValue($this->context)
                ]
            ),
            'createResolver',
            [
                new Variable('this')
            ]
        );
    }
}