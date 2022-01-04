<?php

namespace SilverStripe\GraphQL\Middleware;

use Exception;
use GraphQL\Error\SyntaxError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\OperationParams;
use GraphQL\Server\ServerConfig;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\TokenContextProvider;
use SilverStripe\Security\SecurityToken;

/**
 * Adds functionality that checks a request for a token before allowing a mutation
 * to happen. Protects against CSRF attacks
 *
 */
class CSRFMiddleware implements QueryMiddlewareInterface
{
    /**
     * @param OperationParams[] $operations
     * @param ServerConfig $config
     * @param callable $next
     * @return ExecutionResult|ExecutionResult[]
     * @throws Exception
     * @throws SyntaxError
     */
    public function process(array $operations, ServerConfig $config, callable $next)
    {
        $context = $config->getContext();
        foreach ($operations as $operation) {
            if ($operation->query && QueryHandler::isMutation($operation->query)) {
                if (empty($context['token'])) {
                    throw new Exception('Mutations must provide a CSRF token in the X-CSRF-TOKEN header');
                }

                $token = TokenContextProvider::get($context);
                if (!SecurityToken::inst()->check($token)) {
                    throw new Exception('Invalid CSRF token');
                }
            }
        }

        return $next($operations, $config);
    }
}
