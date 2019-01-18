<?php


namespace SilverStripe\GraphQL\Schema\Encoding\Encoders;


use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface;
use SilverStripe\GraphQL\Schema\Encoding\Helpers;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ResolverEncoderInterface;
use SilverStripe\GraphQL\Schema\Components\DynamicResolver;
use SilverStripe\GraphQL\Schema\Components\AbstractFunction;

class DynamicFunctionEncoder implements ResolverEncoderInterface
{
    /**
     * @param AbstractFunction $resolver
     * @return bool
     */
    public function appliesTo(AbstractFunction $resolver)
    {
        return $resolver instanceof DynamicResolver;
    }

    /**
     * @param AbstractFunction $resolver
     * @return MethodCall
     */
    public function getExpression(AbstractFunction $resolver)
    {
        /* @var \SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface $factory */
        $factory = $resolver->export();
        $context = $factory->getContext();
        $exprArray = [];
        foreach ($context as $key => $val) {
            if ($val instanceof AbstractFunction) {
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