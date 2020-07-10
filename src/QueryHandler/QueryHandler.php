<?php


namespace SilverStripe\GraphQL\QueryHandler;


use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\SourceLocation;
use GraphQL\Type\Schema;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Middleware\MiddlewareConsumer;
use SilverStripe\GraphQL\Permission\MemberAware;
use SilverStripe\GraphQL\Permission\MemberContextProvider;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryProvider;
use SilverStripe\GraphQL\Schema\ContextProvider;
use SilverStripe\ORM\ValidationException;
use InvalidArgumentException;

class QueryHandler implements
    QueryHandlerInterface,
    PersistedQueryProvider,
    ContextProvider,
    MemberContextProvider
{
    use MiddlewareConsumer;
    use Extensible;
    use Injectable;
    use MemberAware;

    /**
     * @var array
     */
    private $extraContext = [];

    /**
     * @var callable
     */
    private $errorFormatter = [self::class, 'formatError'];


    /**
     * @param Schema $schema
     * @param string $query
     * @param array|null $params
     * @return array
     */
    public function query(Schema $schema, string $query, ?array $params = []): array
    {
        $executionResult = $this->queryAndReturnResult($schema, $query, $params);

        // Already in array form
        if (is_array($executionResult)) {
            return $executionResult;
        }
        return $this->serialiseResult($executionResult);
    }

    /**
     * @param Schema $schema
     * @param string $query
     * @param array|null $params
     * @return array|ExecutionResult
     */
    public function queryAndReturnResult(Schema $schema, string $query, ?array $params = [])
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
     * @param $id
     * @return string|null
     */
    public function getQueryFromPersistedID($id): ?string
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
        if (!is_string($key)) {
            throw new InvalidArgumentException(sprintf(
                'Context key must be a string. Got %s',
                gettype($key)
            ));
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
    public function serialiseResult($executionResult)
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
    public function setErrorFormatter(callable $errorFormatter): QueryHandler
    {
        $this->errorFormatter = $errorFormatter;
        return $this;
    }

    /**
     * @return array
     */
    protected function getContextDefaults()
    {
        return [
            'currentUser' => $this->getMemberContext(),
        ];
    }


    /**
     * Call middleware to evaluate a graphql query
     *
     * @param Schema $schema
     * @param string $query Query to invoke
     * @param array $context
     * @param array $variables Variables passed to this query
     * @param callable $last The callback to call after all middlewares
     * @return ExecutionResult|array
     */
    protected function callMiddleware(Schema $schema, $query, $context, $variables, callable $last)
    {
        $this->extend('onBeforeCallMiddleware', $schema, $query, $context, $variables);

        $params = [
            'schema' => $schema,
            'query' => $query,
            'context' => $context,
            'vars' => $variables,
        ];
        $result = $this->executeMiddleware($params, $last);

        $this->extend('onAfterCallMiddleware', $schema, $query, $context, $variables, $result);

        return $result;
    }

    /**
     * More verbose error display defaults.
     *
     * @param Error $exception
     * @return array
     */
    public static function formatError(Error $exception)
    {
        $error = [
            'message' => $exception->getMessage(),
        ];

        $locations = $exception->getLocations();
        if (!empty($locations)) {
            $error['locations'] = array_map(function (SourceLocation $loc) {
                return $loc->toArray();
            }, $locations);
        }

        $previous = $exception->getPrevious();
        if ($previous && $previous instanceof ValidationException) {
            $error['validation'] = $previous->getResult()->getMessages();
        }

        return $error;
    }

}
