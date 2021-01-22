<?php

namespace SilverStripe\GraphQL\Tests\Middleware;

use GraphQL\Type\Schema;
use SilverStripe\GraphQL\Middleware\QueryMiddleware;

class DummyResponseMiddleware implements QueryMiddleware
{
    public function process(Schema $schema, $query, $context, $vars, callable $next)
    {
        return "It was me, {$vars['name']}!";
    }
}
