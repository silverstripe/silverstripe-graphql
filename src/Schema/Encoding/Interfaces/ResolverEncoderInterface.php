<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use PhpParser\Node\Expr;
use SilverStripe\GraphQL\Schema\Components\AbstractFunction;

interface ResolverEncoderInterface
{
    /**
     * @param AbstractFunction $resolver
     * @return bool
     */
    public function appliesTo(AbstractFunction $resolver);

    /**
     * @param AbstractFunction $resolver
     * @return Expr
     */
    public function getExpression(AbstractFunction $resolver);


}