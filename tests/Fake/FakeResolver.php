<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;

class FakeResolver implements ResolverInterface, TestOnly
{
    public function resolve($object, $args, $context, $info)
    {
        return 'resolved';
    }
}
