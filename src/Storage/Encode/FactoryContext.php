<?php

namespace SilverStripe\GraphQL\Storage\Encode;

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

}