<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Factories;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use SilverStripe\Core\Injector\Injectable;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Expr\Variable;
use Closure;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ExpressionProvider;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\RegistryAwareClosureFactoryInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryInterface;

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
