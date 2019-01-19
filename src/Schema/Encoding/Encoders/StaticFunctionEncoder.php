<?php


namespace SilverStripe\GraphQL\Schema\Encoding\Encoders;

use PhpParser\Node\Expr;
use SilverStripe\GraphQL\Schema\Components\AbstractFunction;
use SilverStripe\GraphQL\Schema\Components\StaticFunction;
use SilverStripe\GraphQL\Schema\Encoding\Helpers;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\FunctionEncoderInterface;

class StaticFunctionEncoder implements FunctionEncoderInterface
{
    /**
     * @param AbstractFunction $resolver
     * @return bool
     */
    public function appliesTo(AbstractFunction $resolver)
    {
        return $resolver instanceof StaticFunction;
    }

    /**
     * @param AbstractFunction $resolver
     * @return Expr
     */
    public function getExpression(AbstractFunction $resolver)
    {
        return Helpers::normaliseValue($resolver->export());
    }
}
