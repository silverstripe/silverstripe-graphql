<?php

namespace SilverStripe\GraphQL\Middleware;

use Exception;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use SilverStripe\Security\SecurityToken;

/**
 * Adds functionality to that checks a request for a token before allowing a mutation
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
        if ($query && $this->isMutation($query)) {
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

    /**
     * @param string $query
     * @return bool
     * @throws SyntaxError
     */
    protected function isMutation($query)
    {
        // Simple string matching as a first check to prevent unnecessary static analysis
        if (stristr($query, 'mutation') === false) {
            return false;
        }

        // If "mutation" is the first expression in the query, then it's a mutation.
        if (preg_match('/^\s*'.preg_quote('mutation', '/').'/', $query)) {
            return true;
        }

        // Otherwise, bring in the big guns.
        $document = Parser::parse(new Source($query ?: 'GraphQL'));
        $defs = $document->definitions;
        foreach ($defs as $statement) {
            $options = [
                NodeKind::OPERATION_DEFINITION,
                NodeKind::OPERATION_TYPE_DEFINITION
            ];
            if (!in_array($statement->kind, $options, true)) {
                continue;
            }
            if ($statement->operation === 'mutation') {
                return true;
            }
        }

        return false;
    }
}
