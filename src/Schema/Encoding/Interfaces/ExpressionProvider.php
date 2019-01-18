<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use PhpParser\Node\Expr;

interface ExpressionProvider
{
    /**
     * @return Expr
     */
    public function getExpression();
}