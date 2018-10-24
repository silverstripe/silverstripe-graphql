<?php

namespace SilverStripe\GraphQL\Middleware;

use GraphQL\Schema;
use SilverStripe\GraphQL\Middleware\QueryMiddleware;
use Exception;

class HTTPMethodMiddleware implements QueryMiddleware
{
    public function process(Schema $schema, $query, $context, $params, callable $next)
    {
        $isGET = false;
        $isPOST = false;
        if (isset($context['httpMethod'])) {
            $isGET = $context['httpMethod'] === 'GET';
            $isPOST = $context['httpMethod'] === 'POST';
        }

        if (!$isGET && !$isPOST) {
            throw new Exception('Request method must be POST or GET');
        }

        if (preg_match('/^\s*mutation/', $query)) {
            if (!$isPOST) {
                throw new Exception('Mutations must use the POST request method');
            }
        }

        return $next($schema, $query, $context, $params);
    }
}
