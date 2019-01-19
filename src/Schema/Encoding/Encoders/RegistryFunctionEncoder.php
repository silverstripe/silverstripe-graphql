<?php


namespace SilverStripe\GraphQL\Schema\Encoding\Encoders;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use SilverStripe\GraphQL\Schema\Components\RegistryFunction;
use SilverStripe\GraphQL\Schema\Components\AbstractFunction;
use SilverStripe\GraphQL\Schema\Encoding\Helpers;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\FunctionEncoderInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ClosureFactoryInterface;

class RegistryFunctionEncoder implements FunctionEncoderInterface
{
    /**
     * @param AbstractFunction $resolver
     * @return bool
     */
    public function appliesTo(AbstractFunction $resolver)
    {
        return $resolver instanceof RegistryFunction;
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
                    !empty($exprArray) ? Helpers::normaliseValue($exprArray) : null
                ]
            ),
            new Name('createClosure'),
            [
                new Variable('this')
            ]
        );
    }
}
