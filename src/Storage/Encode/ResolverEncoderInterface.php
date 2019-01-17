<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use PhpParser\Node\Expr;
use SilverStripe\GraphQL\TypeAbstractions\ResolverAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;

interface ResolverEncoderInterface
{
    /**
     * @param ResolverAbstraction $type
     * @return bool
     */
    public function appliesTo(ResolverAbstraction $resolver);

    /**
     * @param ResolverAbstraction $resolver
     * @return Expr
     */
    public function getExpression(ResolverAbstraction $resolver);


}