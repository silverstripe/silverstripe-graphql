<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

use PhpParser\Node\Expr;

interface NamedTypeFetcherInterface
{
    /**
     * @param string $typeStr
     * @return Expr
     */
    public function getExpression($typeStr);
}
