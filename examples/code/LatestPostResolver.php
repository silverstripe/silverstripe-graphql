<?php

namespace MyProject;

use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;

class LatestPostResolver implements ResolverInterface
{
    public function resolve($object, $args, $context, $info)
    {
        return Post::get()->sort('Date', 'DESC')->first();
    }
}
