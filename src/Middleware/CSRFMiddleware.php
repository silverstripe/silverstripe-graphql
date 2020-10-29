<?php

namespace SilverStripe\GraphQL\Middleware;

use Exception;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\Security\SecurityToken;

/**
 * Adds functionality that checks a request for a token before allowing a mutation
 * to happen. Protects against CSRF attacks
 *
 */
class CSRFMiddleware implements Middleware
{
    /**
     * @param array $params
     * @param callable $next
     * @return mixed
     * @throws SyntaxError
     */
    public function process(array $params, callable $next)
    {
        $query = $params['query'] ?? null;
        $context = $params['context'] ?? [];
        if ($query && QueryHandler::isMutation($query)) {
            if (empty($context['token'])) {
                throw new Exception('Mutations must provide a CSRF token in the X-CSRF-TOKEN header');
            }
            $token = $context['token'];

            if (!SecurityToken::inst()->check($token)) {
                throw new Exception('Invalid CSRF token');
            }
        }

        return $next($params);
    }
}
