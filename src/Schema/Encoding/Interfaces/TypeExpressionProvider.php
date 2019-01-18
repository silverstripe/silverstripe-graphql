<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use PhpParser\Node\Expr;
use SilverStripe\GraphQL\Schema\Components\Input;
use SilverStripe\GraphQL\Schema\Components\AbstractType;

interface TypeExpressionProvider
{
    /**
     * @param AbstractType|Input $type
     * @return Expr
     */
    public function getExpression(AbstractType $type);
}
