<?php


namespace SilverStripe\GraphQL\Schema\Encoding\Encoders;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use SilverStripe\GraphQL\Schema\Components\StaticFunction;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface;
use SilverStripe\GraphQL\Schema\Encoding\Helpers;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\FunctionEncoderInterface;
use SilverStripe\GraphQL\Schema\Components\DynamicFunction;
use SilverStripe\GraphQL\Schema\Components\AbstractFunction;

class DynamicFunctionEncoder implements FunctionEncoderInterface
{
    /**
     * @param AbstractFunction $resolver
     * @return bool
     */
    public function appliesTo(AbstractFunction $resolver)
    {
        return $resolver instanceof DynamicFunction;
    }

    /**
     * @param AbstractFunction $resolver
     * @return MethodCall
     */
    public function getExpression(AbstractFunction $resolver)
    {
        /* @var ClosureFactoryInterface $factory */
        $factory = $resolver->export();
        $context = $factory->getContext();
        $exprArray = [];
        foreach ($context as $key => $val) {
            if ($val instanceof DynamicFunction) {
                $exprArray[$key] = $this->getExpression($val);
            } elseif ($val instanceof StaticFunction) {
                $exprArray[$key] = $val->export();
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
