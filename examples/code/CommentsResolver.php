<?php

namespace MyProject\GraphQL;

use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;

class CommentsResolver implements ResolverInterface
{
    public function resolve($object, $args, $context, $info)
    {
		$comments = $object->Comments();
		
		if(isset($args['Today']) && $args['Today']) {
			$comments = $comments->where('DATE(Created) = DATE(NOW())');
		}

		return $comments;
    }

}