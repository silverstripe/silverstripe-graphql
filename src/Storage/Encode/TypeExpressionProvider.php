<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\Type;
use PhpParser\Node\Expr;
use SilverStripe\GraphQL\TypeAbstractions\InputTypeAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;

interface TypeExpressionProvider
{
    /**
     * @param TypeAbstraction|InputTypeAbstraction $type
     * @return Expr
     */
    public function getExpression(TypeAbstraction $type);

}