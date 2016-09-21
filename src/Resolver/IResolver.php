<?php

namespace Chillu\GraphQL\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

interface IResolver {

    /**
     * @param mixed $object The parent resolved object
     * @param array $args Input arguments
     * @param mixed $context The context object hat was passed to GraphQL::execute
     * @param ResolveInfo $info ResolveInfo object
     * @return mixed
     */
    public function resolve($object, array $args, $context, ResolveInfo $info);

}
