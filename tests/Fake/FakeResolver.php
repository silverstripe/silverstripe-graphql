<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\OperationResolver;

class FakeResolver implements OperationResolver, TestOnly
{
    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        return 'resolved';
    }
}
