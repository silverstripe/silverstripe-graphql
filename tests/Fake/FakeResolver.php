<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;

class FakeResolver implements ResolverInterface
{
    public function resolve($object, $args, $context, $info)
    {
    	return 'resolved';
    }

}