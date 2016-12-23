<?php

namespace MyProject\GraphQL;

use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;

class ReadResolver implements ResolverInterface
{
    public function resolve($object, $args, $context, $info)
    {
		$list = Post::get();
		
		if(isset($args['StartingWith'])) {
			$list = $list->filter('Title:StartsWith', $args['StartingWith']);
		}

		return $list;
    }

}