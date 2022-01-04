<?php


namespace SilverStripe\GraphQL\QueryHandler;

use GraphQL\Error\Error;
use GraphQL\Error\SyntaxError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Language\SourceLocation;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Schema as GraphQLSchema;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Middleware\QueryMiddlewareInterface;
use SilverStripe\GraphQL\Permission\MemberAware;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider;
use SilverStripe\GraphQL\Schema\Interfaces\ContextProvider;
use SilverStripe\ORM\ValidationException;

/**
 * This class is responsible for taking query information from a controller,
 * processing it through middlewares, extracting the results from the GraphQL schema,
 * and formatting it into a suitable JSON response.
 */
class QueryHandler implements QueryHandlerInterface
{
    use Extensible;
    use Injectable;
    use Configurable;
    use MemberAware;

    // Deprecated. Remove once all dependencies use UserContextProvider::get($context);
    const CURRENT_USER = 'currentUser';

    /**
     * @var ContextProvider[]
     */
    private $contextProviders = [];

    /**
     * @var callable
     */
    private $errorFormatter = [self::class, 'formatError'];

    /**
     * @var callable | null
     * @config
     */
    private $errorHandler = null;

    /**
     * @var QueryMiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * @var bool
     * @config
     */
    private static $enable_batched_queries = true;

    /**
     * QueryHandler constructor.
     * @param ContextProvider[] $contextProviders
     */
    public function __construct(array $contextProviders = [])
    {
        foreach ($contextProviders as $contextProvider) {
            $this->addContextProvider($contextProvider);
        }
    }

    /**
     * @param OperationParams[]|OperationParams $operations
     * @param GraphQLSchema $schema
     * @return ExecutionResult[]|ExecutionResult
     */
    public function executeOperations($operations, GraphQLSchema $schema)
    {
        if ($this->errorHandler) {
            set_error_handler($this->errorHandler);
        }
        $config = $this->getGraphQLServerConfig($schema);

        $isBatched = is_array($operations);
        if ($isBatched) {
            $operationList = $operations;
        } else {
            $operationList = [$operations];
        }

        $last = function ($operations, $config) use ($isBatched) {
            $helper = new Helper();
            return $isBatched ?
                $helper->executeBatch($config, $operations) :
                $helper->executeOperation($config, $operations[0]);
        };

        return $this->callMiddleware($operationList, $config, $last);
    }

    /**
     * @param GraphQLSchema $schema
     * @return ServerConfig
     */
    public function getGraphQLServerConfig(GraphQLSchema $schema): ServerConfig
    {
        $config = ServerConfig::create([
            'schema' => $schema,
            'context' => $this->getContext(),
            'queryBatching' => $this->config()->get('enable_batched_queries'),
            'persistentQueryLoader' => [self::class, 'loadPersistentQuery']
        ]);

        $this->extend('updateGraphQLServerConfig', $config);

        return $config;
    }

    /**
     * @param string $id
     * @return string
     */
    public static function loadPersistentQuery(string $id): ?string
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
        $context = [];
        foreach ($this->contextProviders as $provider) {
            $context = array_merge($context, $provider->provideContext());
        }

        return $context;
    }

    /**
     * @param ContextProvider $provider
     * @return $this
     */
    public function addContextProvider(ContextProvider $provider): QueryHandlerInterface
    {
        $this->contextProviders[] = $provider;
        return $this;
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
     * @return callable
     */
    public function getErrorFormatter(): callable
    {
        return $this->errorFormatter;
    }

    /**
     * @param callable $errorHandler
     * @return QueryHandler
     */
    public function setErrorHandler(callable $errorHandler): self
    {
        $this->errorHandler = $errorHandler;
        return $this;
    }

    /**
     * @return QueryMiddlewareInterface[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @param QueryMiddlewareInterface[] $middlewares
     * @return $this
     */
    public function setMiddlewares(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof QueryMiddlewareInterface) {
                $this->addMiddleware($middleware);
            }
        }
        return $this;
    }

    /**
     * @param QueryMiddlewareInterface $middleware
     * @return $this
     */
    public function addMiddleware(QueryMiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Call middleware to evaluate a graphql query
     *
     * @param OperationParams|OperationParams[] $operations
     * @param ServerConfig $config
     * @param callable $last The callback to call after all middlewares
     * @return ExecutionResult[]|ExecutionResult
     */
    protected function callMiddleware($operations, ServerConfig $config, callable $last)
    {
        // Reverse middlewares
        $next = $last;

        // Filter out any middlewares that are set to `false`, e.g. via config
        $middlewares = array_reverse(array_filter($this->getMiddlewares()));

        /** @var QueryMiddlewareInterface $middleware */
        foreach ($middlewares as $middleware) {
            $next = function ($operations, $config) use ($middleware, $next) {
                return $middleware->process($operations, $config, $next);
            };
        }

        return $next($operations, $config);
    }

    /**
     * More verbose error display defaults.
     *
     * @param Error $exception
     * @return array
     */
    public static function formatError(Error $exception): array
    {
        $current = $exception;
        $relevant = $exception->getPrevious() ?: $current;
        $error = [
            'message' => $relevant->getMessage(),
        ];

        if (Director::isDev()) {
            $error['code'] = $relevant->getCode();
            $error['file'] = $relevant->getFile();
            $error['line'] = $relevant->getLine();
            $error['trace'] = json_encode($relevant->getTrace(), JSON_PRETTY_PRINT);
        }


        $locations = $current->getLocations();
        if (!empty($locations)) {
            $error['locations'] = array_map(function (SourceLocation $loc) {
                return $loc->toArray();
            }, $locations);
        }

        if ($relevant instanceof ValidationException) {
            $error['validation'] = $relevant->getResult()->getMessages();
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
        if (preg_match('/^\s*' . preg_quote('mutation', '/') . '/', $query)) {
            return true;
        }

        // Otherwise, bring in the big guns.
        $document = Parser::parse(new Source($query));
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
