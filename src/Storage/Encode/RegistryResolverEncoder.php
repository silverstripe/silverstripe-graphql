<?php


namespace SilverStripe\GraphQL\Storage\Encode;


use SilverStripe\GraphQL\TypeAbstractions\RegistryResolverAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\ResolverAbstraction;

class RegistryResolverEncoder implements ResolverEncoderInterface
{
    /**
     * @param ResolverAbstraction $resolver
     * @return bool
     */
    public function appliesTo(ResolverAbstraction $resolver)
    {
        return $resolver instanceof RegistryResolverAbstraction;
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
            new Name('createClosure'),
            [
                new Variable('this')
            ]
        );

    }
}