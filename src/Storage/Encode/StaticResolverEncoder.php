<?php


namespace SilverStripe\GraphQL\Storage\Encode;


use PhpParser\Node\Expr;
use SilverStripe\GraphQL\TypeAbstractions\ResolverAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\StaticResolverAbstraction;

class StaticResolverEncoder implements ResolverEncoderInterface
{
    /**
     * @param ResolverAbstraction $resolver
     * @return bool
     */
    public function appliesTo(ResolverAbstraction $resolver)
    {
        return $resolver instanceof StaticResolverAbstraction;
    }

    /**
     * @param ResolverAbstraction $resolver
     * @return Expr
     */
    public function getExpression(ResolverAbstraction $resolver)
    {
        return Helpers::normaliseValue($resolver->export());
    }
}