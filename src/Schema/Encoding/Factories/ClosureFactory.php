<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Factories;

use PhpParser\Node\Expr;
use SilverStripe\Core\Injector\Injectable;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Expr\New_;
use Closure;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ExpressionProvider;

abstract class ClosureFactory implements ClosureFactoryInterface, ExpressionProvider
{
    use Injectable;
    use FactoryContext;

    /**
     * @return Closure
     */
    abstract public function createClosure();

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
            new Name('createClosure')
        );
    }
}
