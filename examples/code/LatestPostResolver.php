<?php

namespace MyProject;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\OperationResolver;

class LatestPostResolver implements OperationResolver
{
    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        return Post::get()->sort('Date', 'DESC')->first();
    }
}
