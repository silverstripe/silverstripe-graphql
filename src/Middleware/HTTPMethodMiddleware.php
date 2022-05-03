<?php

namespace SilverStripe\GraphQL\Middleware;

use Exception;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use SilverStripe\GraphQL\QueryHandler\RequestContextProvider;

/**
 * Ensures mutations use POST requests
 */
class HTTPMethodMiddleware implements QueryMiddleware
{
    /**
     * @inheritDoc
     */
    public function process(Schema $schema, string $query, array $context, array $vars, callable $next)
    {
        $isGET = false;
        $isPOST = false;
        $method = RequestContextProvider::get($context);
        if ($method) {
            $isGET = $method === 'GET';
            $isPOST = $method === 'POST';
        }

        if (!$isGET && !$isPOST) {
            throw new Exception('Request method must be POST or GET');
        }

        if (preg_match('/^\s*mutation/', $query ?? '')) {
            if (!$isPOST) {
                throw new Exception('Mutations must use the POST request method');
            }
        }

        return $next($schema, $query, $context, $vars);
    }
}
