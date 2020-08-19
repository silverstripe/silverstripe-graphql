<?php

namespace SilverStripe\GraphQL\Middleware;

use Exception;

class HTTPMethodMiddleware implements Middleware
{
    /**
     * @param array $params
     * @param callable $next
     * @return mixed
     * @throws Exception
     */
    public function process(array $params, callable $next)
    {
        $context = $params['context'] ?? [];
        $query = $params['query'] ?? null;

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

        return $next($params);
    }
}
