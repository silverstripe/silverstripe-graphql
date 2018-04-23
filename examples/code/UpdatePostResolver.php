<?php

namespace MyProject;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\OperationResolver;

class UpdatePostResolver implements OperationResolver
{
    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        $post = Post::get()->byID($args['ID']);

        if ($post->canEdit()) {
            $post->Title = $args['NewTitle'];
            $post->write();
        }

        return $post;
    }
}
