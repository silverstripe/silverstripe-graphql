<?php

namespace SilverStripe\GraphQL\Middleware;

use SilverStripe\GraphQL\Schema\Components\Schema;
use SilverStripe\Security\SecurityToken;
use Exception;

class CSRFMiddleware implements QueryMiddleware
{
    /**
     * @param Schema $schema
     * @param string $query
     * @param array $context
     * @param array $params
     * @param callable $next
     * @return array|\GraphQL\Executor\ExecutionResult
     * @throws Exception
     */
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
