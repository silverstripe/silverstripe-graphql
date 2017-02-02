<?php

namespace MyProject;

use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;

class UpdatePostResolver implements ResolverInterface
{
    public function resolve($object, $args, $context, $info)
    {
		$post = Post::get()->byID($args['ID']);
		
		if($post->canEdit()) {
			$post->Title = $args['NewTitle'];
			$post->write();
		}

		return $post;
    }

}