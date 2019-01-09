<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
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
            new New_(
                new Name(get_class($this)),
                [
                    $this->getContextExpression()
                ]
            ),
            new Name('createClosure'),
            [
                new Variable('this')
            ]
        );
    }

}