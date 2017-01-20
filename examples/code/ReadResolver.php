<?php

namespace MyProject;

use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;

class ReadResolver implements ResolverInterface
{
    public function resolve($object, $args, $context, $info)
    {
		$list = Post::get();
		
		if(isset($args['Title'])) {
			$list = $list->filter('Title:PartialMatch', $args['Title']);
		}

		return $list;
    }

}