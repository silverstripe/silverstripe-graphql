<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use PhpParser\Node\Expr;
use SilverStripe\GraphQL\Schema\Components\AbstractFunction;

/**
 * Converts an {@link AbstractFunction} into a PHP expression
 * which can be persisted as generated code. This expression will usually create (not execute)
 * PHP code which creates a factory. This factory in turn can create a closure.
 * If the function or factory instances have context attached,
 * this context can be passed into the closure.
 * This avoids the closed over values from needing to be persisted as a PHP expression as well.
 */
interface FunctionEncoderInterface
{
    /**
     * @param AbstractFunction $resolver
     * @return bool
     */
    public function appliesTo(AbstractFunction $resolver);

    /**
     * @param AbstractFunction $resolver
     * @return Expr
     */
    public function getExpression(AbstractFunction $resolver);
}
