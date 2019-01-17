<?php

namespace SilverStripe\GraphQL\Middleware;

use GraphQL\Type\Schema;
use Exception;
use SilverStripe\GraphQL\TypeAbstractions\SchemaAbstraction;

class HTTPMethodMiddleware implements QueryMiddleware
{
    /**
     * @param SchemaAbstraction $schema
     * @param string $query
     * @param array $context
     * @param array $params
     * @param callable $next
     * @return array|\GraphQL\Executor\ExecutionResult
     * @throws Exception
     */
    public function process(SchemaAbstraction $schema, $query, $context, $params, callable $next)
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
