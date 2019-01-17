<?php


namespace SilverStripe\GraphQL\Storage\Encode;


use PhpParser\Node\Expr\MethodCall;
use SilverStripe\GraphQL\TypeAbstractions\DynamicResolverAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\ResolverAbstraction;

class DynamicResolverEncoder implements ResolverEncoderInterface
{
    /**
     * @param ResolverAbstraction $resolver
     * @return bool
     */
    public function appliesTo(ResolverAbstraction $resolver)
    {
        return $resolver instanceof DynamicResolverAbstraction;
    }

    /**
     * @param ResolverAbstraction $resolver
     * @return MethodCall
     */
    public function getExpression(ResolverAbstraction $resolver)
    {
        /* @var ClosureFactoryInterface $factory */
        $factory = $resolver->export();
        $context = $factory->getContext();
        $exprArray = [];
        foreach ($context as $key => $val) {
            if ($val instanceof ResolverAbstraction) {
                $exprArray[$key] = $this->getExpression($val);
            } else {
                $exprArray[$key] = $val;
            }
        }
        return new MethodCall(
            new New_(
                new Name(get_class($field)),
                [
                    Helpers::normaliseValue($exprArray)
                ]
            ),
            new Name('createClosure')
        );

    }
}