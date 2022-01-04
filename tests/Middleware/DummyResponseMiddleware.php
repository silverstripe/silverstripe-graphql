<?php

namespace SilverStripe\GraphQL\Tests\Middleware;

use SilverStripe\GraphQL\Middleware\QueryMiddlewareInterface;

class DummyResponseMiddleware implements QueryMiddlewareInterface
{
    public function process($operations, $config, callable $next)
    {
        $vars = $operations[0]->variables;
        return "It was me, {$vars['name']}!";
    }
}
