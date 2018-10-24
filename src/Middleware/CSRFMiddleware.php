<?php

namespace SilverStripe\GraphQL\Middleware;

use GraphQL\Schema;
use SilverStripe\GraphQL\Middleware\QueryMiddleware;
use SilverStripe\Security\SecurityToken;
use Exception;

class CSRFMiddleware implements QueryMiddleware
{
    public function process(Schema $schema, $query, $context, $params, callable $next)
    {
        if (preg_match('/^\s*mutation/', $query)) {
            if (empty($context['token'])) {
                throw new Exception('Mutations must provide a CSRF token in the X-CSRF-TOKEN header');
            }
            $token = $context['token'];

            if (!SecurityToken::inst()->check($token)) {
                throw new Exception('Invalid CSRF token');
            }
        }

        return $next($schema, $query, $context, $params);
    }
}
