<?php

namespace MyProject;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\OperationResolver;

class CommentsResolver implements OperationResolver
{
    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        /** @var Post $object */
        $comments = $object->Comments();

        if (isset($args['Today']) && $args['Today']) {
            $comments = $comments->where('DATE(Created) = DATE(NOW())');
        }

        return $comments;
    }
}
