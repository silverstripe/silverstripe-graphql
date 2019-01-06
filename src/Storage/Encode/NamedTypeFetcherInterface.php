<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use PhpParser\Node\Expr;

interface NamedTypeFetcherInterface
{
    /**
     * @param string $typeStr
     * @return Expr
     */
    public function getExpression($typeStr);
}