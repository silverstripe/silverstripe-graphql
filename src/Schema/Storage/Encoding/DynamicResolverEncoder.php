<?php


namespace SilverStripe\GraphQL\Schema\Storage\Encoding\Encoders;


use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use SilverStripe\GraphQL\Storage\Encode\ClosureFactoryInterface;
use SilverStripe\GraphQL\Storage\Encode\Helpers;
use SilverStripe\GraphQL\Storage\Encode\ResolverEncoderInterface;
use SilverStripe\GraphQL\Schema\Components\DynamicResolverAbstraction;
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
                new Name(get_class($factory)),
                [
                    Helpers::normaliseValue($exprArray)
                ]
            ),
            new Name('createClosure')
        );

    }
}