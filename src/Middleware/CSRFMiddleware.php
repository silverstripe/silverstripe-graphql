<?php

namespace SilverStripe\GraphQL\Middleware;

use Exception;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Type\Schema;
use SilverStripe\GraphQL\Manager;
use SilverStripe\Security\SecurityToken;

class CSRFMiddleware implements QueryMiddleware
{
    public function process(Schema $schema, $query, $context, $params, callable $next)
    {
        if ($this->isMutation($query)) {
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

    /**
     * @param string $query
     * @return bool
     */
    protected function isMutation($query)
    {
        // Simple string matching as a first check to prevent unnecessary static analysis
        if (stristr($query, Manager::MUTATION_ROOT) === false) {
            return false;
        }

        // If "mutation" is the first expression in the query, then it's a mutation.
        if (preg_match('/^\s*'.preg_quote(Manager::MUTATION_ROOT, '/').'/', $query)) {
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
            if ($statement->operation === Manager::MUTATION_ROOT) {
                return true;
            }
        }

        return false;
    }
}
