<?php

namespace SilverStripe\GraphQL\Tests\Middleware;

use GraphQL\Type\Schema;
use SilverStripe\GraphQL\Middleware\QueryMiddleware;

class DummyResponseMiddleware implements QueryMiddleware
{
    public function process(Schema $schema, $query, $context, $params, callable $next)
    {
        return ['result' => "It was me, {$params['name']}!"];
    }
}
