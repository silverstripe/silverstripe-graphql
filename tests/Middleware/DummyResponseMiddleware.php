<?php

namespace SilverStripe\GraphQL\Tests\Middleware;

use GraphQL\Type\Schema;
use SilverStripe\GraphQL\Middleware\Middleware;

class DummyResponseMiddleware implements Middleware
{
    public function process(array $params, callable $next)
    {
        return ['result' => "It was me, {$params['vars']['name']}!"];
    }
}
