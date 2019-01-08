<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use PhpParser\Node\Expr;
use SilverStripe\Core\Injector\Injectable;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Expr\Variable;
use Closure;

abstract class RegistryAwareClosureFactory implements RegistryAwareClosureFactoryInterface, ExpressionProvider
{
    use Injectable;
    use FactoryContext;

    /**
     * @param TypeRegistryInterface $registry
     * @return Closure
     */
    abstract public function createClosure(TypeRegistryInterface $registry);

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
            'createClosure',
            [
                new Variable('this')
            ]
        );
    }
}