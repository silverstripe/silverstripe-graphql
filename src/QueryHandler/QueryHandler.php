<?php


namespace SilverStripe\GraphQL\QueryHandler;

use GraphQL\Error\Error;
use GraphQL\Error\SyntaxError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Language\SourceLocation;
use GraphQL\Type\Schema as GraphQLSchema;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Middleware\Middleware;
use SilverStripe\GraphQL\Middleware\MiddlewareConsumer;
use SilverStripe\GraphQL\Permission\MemberAware;
use SilverStripe\GraphQL\Permission\MemberContextProvider;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\ContextProvider;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\ValidationException;
use InvalidArgumentException;

/**
 * This class is responsible for taking query information from a controller,
 * processing it through middlewares, extracting the results from the GraphQL schema,
 * and formatting it into a suitable JSON response.
 */
class QueryHandler implements
    QueryHandlerInterface,
    PersistedQueryProvider,
    ContextProvider,
    MemberContextProvider
{
    use MiddlewareConsumer;
    use Extensible;
    use Injectable;
    use Configurable;
    use MemberAware;

    /**
     * The context key that refers the the logged in user
     */
    const CURRENT_USER = 'currentUser';

    /**
     * @var array
     */
    private $extraContext = [];

    /**
     * @var callable
     */
    private $errorFormatter = [self::class, 'formatError'];

    /**
     * @param GraphQLSchema $schema
     * @param string $query
     * @param array|null $params
     * @return array
     */
    public function query(GraphQLSchema $schema, string $query, ?array $params = []): array
    {
        $executionResult = $this->queryAndReturnResult($schema, $query, $params);

        // Already in array form
        if (is_array($executionResult)) {
            return $executionResult;
        }
        return $this->serialiseResult($executionResult);
    }

    /**
     * @param GraphQLSchema $schema
     * @param string $query
     * @param array|null $params
     * @return array|ExecutionResult
     */
    public function queryAndReturnResult(GraphQLSchema $schema, string $query, ?array $params = [])
    {
        $context = $this->getContext();
        $last = function ($params) {
            $schema = $params['schema'];
            $query = $params['query'];
            $context = $params['context'];
            $params = $params['vars'];
            return GraphQL::executeQuery($schema, $query, null, $context, $params);
        };

        return $this->callMiddleware($schema, $query, $context, $params, $last);
    }


    /**
     * get query from persisted id, return null if not found
     *
     * @param string $id
     * @return string|null
     */
    public function getQueryFromPersistedID(string $id): ?string
    {
        /** @var PersistedQueryMappingProvider $provider */
        $provider = Injector::inst()->get(PersistedQueryMappingProvider::class);

        return $provider->getByID($id);
    }

    /**
     * Get global context to pass to $context for all queries
     *
     * @return array
     */
    public function getContext(): array
    {
        return array_merge(
            $this->getContextDefaults(),
            $this->extraContext
        );
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return ContextProvider
     */
    public function addContext(string $key, $value): ContextProvider
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Context key cannot be empty');
        }
        $this->extraContext[$key] = $value;

        return $this;
    }

    /**
     * Serialise a Graphql result object for output
     *
     * @param ExecutionResult $executionResult
     * @return array
     */
    public function serialiseResult(ExecutionResult $executionResult): array
    {
        // Format object
        if (!empty($executionResult->errors)) {
            return [
                'data' => $executionResult->data,
                'errors' => array_map($this->errorFormatter, $executionResult->errors),
            ];
        } else {
            return [
                'data' => $executionResult->data,
            ];
        }
    }

    /**
     * @param callable $errorFormatter
     * @return QueryHandler
     */
    public function setErrorFormatter(callable $errorFormatter): self
    {
        $this->errorFormatter = $errorFormatter;
        return $this;
    }

    /**
     * @return array
     */
    protected function getContextDefaults(): array
    {
        return [
            self::CURRENT_USER => $this->getMemberContext(),
        ];
    }


    /**
     * Call middleware to evaluate a graphql query
     *
     * @param GraphQLSchema $schema
     * @param string $query Query to invoke
     * @param array $context
     * @param array $variables Variables passed to this query
     * @param callable $last The callback to call after all middlewares
     * @return ExecutionResult|array
     */
    protected function callMiddleware(GraphQLSchema $schema, $query, $context, $variables, callable $last)
    {

        $params = [
            'schema' => $schema,
            'query' => $query,
            'context' => $context,
            'vars' => $variables,
        ];
        $result = $this->executeMiddleware($params, $last);

        return $result;
    }

    /**
     * More verbose error display defaults.
     *
     * @param Error $exception
     * @return array
     */
    public static function formatError(Error $exception): array
    {
        $error = [
            'message' => $exception->getMessage(),
        ];

        if (Director::isDev()) {
            $error['code'] = $exception->getCode();
            $error['file'] = $exception->getFile();
            $error['line'] = $exception->getLine();
            $error['trace'] = $exception->getTraceAsString();
        }


        $locations = $exception->getLocations();
        if (!empty($locations)) {
            $error['locations'] = array_map(function (SourceLocation $loc) {
                return $loc->toArray();
            }, $locations);
        }

        $previous = $exception->getPrevious();
        if ($previous && $previous instanceof ValidationException) {
            $errorx['validation'] = $previous->getResult()->getMessages();
        }

        return $error;
    }

    /**
     * @param string $query
     * @return bool
     * @throws SyntaxError
     */
    public static function isMutation(string $query): bool
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
