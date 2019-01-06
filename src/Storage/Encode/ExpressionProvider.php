<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use PhpParser\Node\Expr;

interface ExpressionProvider
{
    /**
     * @return Expr
     */
    public function getExpression();
}