<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Factories;

use PhpParser\Node\Expr;
use SilverStripe\GraphQL\Schema\Encoding\Helpers;

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
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return Expr
     */
    protected function getContextExpression()
    {
        return Helpers::normaliseValue($this->context);
    }

}