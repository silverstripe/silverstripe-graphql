<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use PhpParser\Node\Expr;

trait FactoryContext
{
    /**
     * @var array
     */
    protected $context = [];

    /**
     * ResolverFactory constructor.
     * @param array $context
     */
    public function __construct($context = [])
    {
        $this->context = $context;
    }

    /**
     * @return Expr
     */
    protected function getContextExpression()
    {
        return Helpers::normaliseValue($this->context);
    }

}