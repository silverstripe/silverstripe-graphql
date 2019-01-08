<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use PhpParser\Node\Expr;
use SilverStripe\Core\Injector\Injectable;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use Closure;

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
            new StaticCall(
                new Name(get_class($this)),
                'create',
                [
                    $this->getContextExpression(),
                ]
            ),
            'createClosure'
        );
    }

    /**
     * @return Expr
     */
    protected function getContextExpression()
    {
        return Helpers::normaliseValue($this->context);
    }
}