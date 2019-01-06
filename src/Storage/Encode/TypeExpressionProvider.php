<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\Type;
use PhpParser\Node\Expr;

interface TypeExpressionProvider
{
    /**
     * @param Type|InputType $type
     * @return Expr
     */
    public function getExpression(Type $type);

}