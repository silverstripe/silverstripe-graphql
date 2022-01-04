<?php

namespace SilverStripe\GraphQL\Middleware;

use Exception;
use GraphQL\Error\SyntaxError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\OperationParams;
use GraphQL\Server\ServerConfig;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\RequestContextProvider;

/**
 * Ensures mutations use POST requests
 */
class HTTPMethodMiddleware implements QueryMiddlewareInterface
{
    /**
     * @param OperationParams[] $operations
     * @param ServerConfig $config
     * @param callable $next
     * @return ExecutionResult|ExecutionResult[]
     * @throws Exception
     * @throws SyntaxError
     */
    public function process($operations, ServerConfig $config, callable $next)
    {
        $isGET = false;
        $isPOST = false;
        $method = RequestContextProvider::get($config->getContext());
        if ($method) {
            $isGET = $method === 'GET';
            $isPOST = $method === 'POST';
        }

        if (!$isGET && !$isPOST) {
            throw new Exception('Request method must be POST or GET');
        }

        if (!$isPOST) {
            foreach ($operations as $operation) {
                if ($operation->query && QueryHandler::isMutation($operation->query)) {
                    throw new Exception('Mutations must use the POST request method');
                }
            }
        }

        return $next($operations, $config);
    }
}
