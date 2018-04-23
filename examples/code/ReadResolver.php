<?php

namespace MyProject;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\OperationResolver;

class ReadResolver implements OperationResolver
{
    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        $list = Post::get();

        if (isset($args['Title'])) {
            $list = $list->filter('Title:PartialMatch', $args['Title']);
        }

        return $list;
    }
}
