<?php

namespace SilverStripe\GraphQL\Middleware;

use Exception;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\TokenContextProvider;
use SilverStripe\Security\SecurityToken;

/**
 * Adds functionality that checks a request for a token before allowing a mutation
 * to happen. Protects against CSRF attacks
 *
 */
class CSRFMiddleware implements QueryMiddleware
{
    /**
     * @inheritDoc
     */
    public function process(Schema $schema, string $query, array $context, array $vars, callable $next)
    {
        if ($query && QueryHandler::isMutation($query)) {
            if (empty($context['token'])) {
                throw new Exception('Mutations must provide a CSRF token in the X-CSRF-TOKEN header');
            }
            $token = TokenContextProvider::get($context);

            if (!SecurityToken::inst()->check($token)) {
                throw new Exception('Invalid CSRF token');
            }
        }

        return $next($schema, $query, $context, $vars);
    }
}
