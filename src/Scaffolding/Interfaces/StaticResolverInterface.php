<?php

namespace SilverStripe\GraphQL\Scaffolding\Interfaces;

use GraphQL\Type\Definition\ResolveInfo;

interface StaticResolverInterface
{
    /**
     * Invoked by the Executor class to resolve this mutation / query
     * @see Executor
     *
     * @param mixed $object
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public static function resolve($object, array $args, $context, ResolveInfo $info);

}