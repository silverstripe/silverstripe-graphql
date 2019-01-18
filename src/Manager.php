<?php

namespace SilverStripe\GraphQL;

use InvalidArgumentException;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\SourceLocation;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\Injectable;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\Schema\QueryResultInterface;
use SilverStripe\GraphQL\Schema\SchemaHandlerInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryInterface;
use SilverStripe\GraphQL\Schema\SchemaStorageInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Middleware\QueryMiddleware;
use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\Schema;
use SilverStripe\GraphQL\Schema\Components\AbstractType;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\Security\Security;
use BadMethodCallException;

/**
 * Manager is the master container for a graphql endpoint, and contains
 * all queries, mutations, and types.
 *
 * Instantiate with {@see Manager::createFromConfig()} with a config array.
 */
class Manager implements ConfigurationApplier, TypeRegistryInterface
{
    use Injectable;
    use Extensible;
    use Configurable;

    const QUERY_ROOT = 'query';

    const MUTATION_ROOT = 'mutation';

    const TYPES_ROOT = 'types';

    /**
     * @var array
     */
    private static $dependencies = [
        'schemaHandler' => '%$' . SchemaHandlerInterface::class,
    ];

    /**
     * @var string
     */
    protected $schemaKey;

    /**
     * Map of named {@link Type}
     *
     * @var Type[]
     */
    protected $types = [];

    /**
     * @var array Map of named arrays
     */
    protected $queries = [];

    /**
     * @var array Map of named arrays
     */
    protected $mutations = [];

    /**
     * @var callable
     */
    protected $errorFormatter = [self::class, 'formatError'];

    /**
     * @var SchemaHandlerInterface
     */
    protected $schemaHandler;

    /**
     * @var Member
     */
    protected $member;

    /**
     * @var QueryMiddleware[]
     */
    protected $middlewares = [];

    /**
     * @var array
     */
    protected $extraContext = [];

    /**
     * @var SchemaStorageInterface
     */
    protected $schemaStore;

    /**
     * @param string $schemaKey
     * @param SchemaStorageInterface $schemaStore
     */
    public function __construct($schemaKey, SchemaStorageInterface $schemaStore)
    {
        $this->setSchemaKey($schemaKey);
        $this->setSchemaStore($schemaStore);
    }

    /**
     * @param $config
     * @param string $schemaKey
     * @return Manager
     * @throws NotFoundExceptionInterface
     * @deprecated 4.0
     */
    public static function createFromConfig($config, $schemaKey = null)
    {
        Deprecation::notice('4.0', 'Use applyConfig() on a new instance instead');

        $manager = new static($schemaKey);

        return $manager->applyConfig($config);
    }

    /**
     * @return QueryMiddleware[]
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * @param QueryMiddleware[] $middlewares
     * @return $this
     */
    public function setMiddlewares($middlewares)
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * @param QueryMiddleware $middleware
     * @return $this
     */
    public function addMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * @param array $config An array with optional 'types' and 'queries' keys
     * @return Manager
     * @throws NotFoundExceptionInterface
     */
    public function applyConfig(array $config)
    {
        $this->extend('updateConfig', $config);

        // Bootstrap schema class mapping from config
        if ($config && array_key_exists('typeNames', $config)) {
            StaticSchema::inst()->setTypeNames($config['typeNames']);
        }

        // Types (incl. Interfaces and InputTypes)
        if ($config && array_key_exists('types', $config)) {
            foreach ($config['types'] as $name => $typeCreatorClass) {
                $typeCreator = Injector::inst()->create($typeCreatorClass, $this);
                if (!($typeCreator instanceof TypeCreator)) {
                    throw new InvalidArgumentException(sprintf(
                        'The type named "%s" needs to be a class extending ' . TypeCreator::class,
                        $name
                    ));
                }

                $type = $typeCreator->toType();
                $this->addType($type, $name);
            }
        }

        // Queries
        if ($config && array_key_exists('queries', $config)) {
            foreach ($config['queries'] as $name => $queryCreatorClass) {
                $queryCreator = Injector::inst()->create($queryCreatorClass, $this);
                if (!($queryCreator instanceof QueryCreator)) {
                    throw new InvalidArgumentException(sprintf(
                        'The type named "%s" needs to be a class extending ' . QueryCreator::class,
                        $name
                    ));
                }

                $this->addQuery($queryCreator->toField(), $name);
                foreach ($queryCreator->extraTypes() as $type) {
                    $this->addType($type);
                }
            }
        }

        // Mutations
        if ($config && array_key_exists('mutations', $config)) {
            foreach ($config['mutations'] as $name => $mutationCreatorClass) {
                $mutationCreator = Injector::inst()->create($mutationCreatorClass, $this);
                if (!($mutationCreator instanceof MutationCreator)) {
                    throw new InvalidArgumentException(sprintf(
                        'The mutation named "%s" needs to be a class extending ' . MutationCreator::class,
                        $name
                    ));
                }

                $this->addMutation($mutationCreator->toField(), $name);
                foreach ($mutationCreator->extraTypes() as $type) {
                    $this->addType($type);
                }

            }
        }

        if (isset($config['scaffolding'])) {
            $scaffolder = SchemaScaffolder::createFromConfig($config['scaffolding']);
        } else {
            $scaffolder = new SchemaScaffolder();
        }
        if (isset($config['scaffolding_providers'])) {
            foreach ($config['scaffolding_providers'] as $provider) {
                if (!class_exists($provider)) {
                    throw new InvalidArgumentException(sprintf(
                        'Scaffolding provider %s does not exist.',
                        $provider
                    ));
                }

                $provider = Injector::inst()->create($provider);
                if (!$provider instanceof ScaffoldingProvider) {
                    throw new InvalidArgumentException(sprintf(
                        'All scaffolding providers must implement the %s interface',
                        ScaffoldingProvider::class
                    ));
                }
                $provider->provideGraphQLScaffolding($scaffolder);
            }
        }

        $scaffolder->addToManager($this);

        return $this;
    }

    /**
     * Call middleware to evaluate a graphql query
     *
     * @param Schema $schema
     * @param string $query Query to invoke
     * @param array $context
     * @param array $params Variables passed to this query
     * @param callable $last The callback to call after all middlewares
     * @return ExecutionResult|array
     */
    protected function callMiddleware(Schema $schema, $query, $context, $params, callable $last)
    {
        // Reverse middlewares
        $next = $last;
        // Filter out any middlewares that are set to `false`, e.g. via config
        $middlewares = array_reverse(array_filter($this->getMiddlewares()));
        /** @var QueryMiddleware $middleware */
        foreach ($middlewares as $middleware) {
            $next = function ($schema, $query, $context, $params) use ($middleware, $next) {
                return $middleware->process($schema, $query, $context, $params, $next);
            };
        }
        return $next($schema, $query, $context, $params);
    }

    /**
     * Execute an arbitrary operation (mutation / query) on this schema.
     *
     * Note because middleware may produce serialised responses we need to conditionally
     * normalise to serialised array on output from object -> array.
     *
     * @param string $query Raw query
     * @param array $params List of arguments given for this operation
     * @return array
     */
    public function query($query, $params = [])
    {
        if (!$this->getSchemaStore()->exists()) {
            throw new BadMethodCallException(sprintf(
                'No stored schema available. Did you forget to generate it using %s::regenerate() or through the dev task?',
                __CLASS__
            ));
        }

        $executionResult = $this->queryAndReturnResult($query, $params);
        // Already in array form
        if (is_array($executionResult)) {
            return $executionResult;
        }
        return $this->serialiseResult($executionResult);
    }

    /**
     * Evaluate query via middleware
     *
     * @param string $query
     * @param array $params
     * @return QueryResultInterface|array Result as either source object result, or serialised as array.
     * @throws NotFoundExceptionInterface
     */
    public function queryAndReturnResult($query, $params = [])
    {
        $schemaAbstract = $this->getSchemaStore()->load();
        $context = $this->getContext();

        $last = function ($schemaAbstract, $query, $context, $params) {
            return $this->getSchemaHandler()->query(
                $schemaAbstract,
                $query,
                null,
                $context,
                $params
            );
        };

        return $this->callMiddleware($schemaAbstract, $query, $context, $params, $last);
    }

    /**
     * Register a new type
     *
     * @param \SilverStripe\GraphQL\Schema\Components\AbstractType $type
     * @param string $name An optional identifier for this type (defaults to 'name'
     * attribute in type definition). Needs to be unique in schema.
     */
    public function addType(AbstractType $type, $name = '')
    {
        if (!$name) {
            $name = $type->getName();
        }
        $this->types[$name] = $type;
    }

    /**
     * Return a type definition by name
     *
     * @param string $name
     * @return AbstractType
     */
    public function getType($name)
    {
        if (isset($this->types[$name])) {
            return $this->types[$name];
        }

        throw new InvalidArgumentException("Type '$name' is not a registered GraphQL type");
    }

    /**
     * @param  string $name
     *
     * @return boolean
     */
    public function hasType($name)
    {
        return isset($this->types[$name]);
    }

    /**
     * Register a new Query. Query can be defined as a closure to ensure
     * dependent types are lazy loaded.
     *
     * @param Field $query
     * @param string $name Identifier for this query (unique in schema)
     */
    public function addQuery(Field $query, $name)
    {
        $this->queries[$name] = $query;
    }

    /**
     * Get a query by name
     *
     * @param string $name
     * @return \SilverStripe\GraphQL\Schema\Components\Field
     */
    public function getQuery($name)
    {
        return $this->queries[$name];
    }

    /**
     * Register a new mutation. Mutations can be callbacks to ensure
     * dependent types are lazy-loaded.
     *
     * @param \SilverStripe\GraphQL\Schema\Components\Field
     * @param string $name Identifier for this mutation (unique in schema)
     */
    public function addMutation(Field $mutation, $name)
    {
        $this->mutations[$name] = $mutation;
    }

    /**
     * Get a mutation by name
     *
     * @param string $name
     * @return \SilverStripe\GraphQL\Schema\Components\Field
     */
    public function getMutation($name)
    {
        return $this->mutations[$name];
    }

    /**
     * @return SchemaHandlerInterface
     */
    public function getSchemaHandler()
    {
        return $this->schemaHandler;
    }

    /**
     * @param SchemaHandlerInterface $schemaHandler
     * @return Manager
     */
    public function setSchemaHandler(SchemaHandlerInterface $schemaHandler)
    {
        $this->schemaHandler = $schemaHandler;

        return $this;
    }

    /**
     * @param \SilverStripe\GraphQL\Schema\Storage\SchemaStorageInterface $store
     * @return $this
     */
    public function setSchemaStore(SchemaStorageInterface $store)
    {
        $this->schemaStore = $store;

        return $this;
    }

    /**
     * @return \SilverStripe\GraphQL\Schema\Storage\SchemaStorageInterface
     */
    public function getSchemaStore()
    {
        return $this->schemaStore;
    }

    /**
     * @return string
     */
    public function getSchemaKey()
    {
        return $this->schemaKey;
    }

    /**
     * @param string $schemaKey
     * @return $this
     */
    public function setSchemaKey($schemaKey)
    {
        if (!is_string($schemaKey)) {
            throw new InvalidArgumentException(sprintf(
                '%s schemaKey must be a string',
                __CLASS__
            ));
        }
        if (empty($schemaKey)) {
            throw new InvalidArgumentException(sprintf(
                '%s schemaKey must cannot be empty',
                __CLASS__
            ));
        }
        if (preg_match('/[^A-Za-z0-9_-]/', $schemaKey)) {
            throw new InvalidArgumentException(sprintf(
                '%s schemaKey may only contain alphanumeric characters, dashes, and underscores',
                __CLASS__
            ));
        }

        $this->schemaKey = $schemaKey;

        return $this;
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

    /**
     * Set the Member for the current context
     *
     * @param  Member $member
     * @return $this
     */
    public function setMember(Member $member)
    {
        $this->member = $member;
        return $this;
    }

    /**
     * Get the Member for the current context either from a previously set value or the current user
     *
     * @return Member
     */
    public function getMember()
    {
        return $this->member ?: Security::getCurrentUser();
    }

    /**
     * get query from persisted id, return null if not found
     *
     * @param $id
     * @return string | null
     * @throws NotFoundExceptionInterface
     */
    public function getQueryFromPersistedID($id)
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
    protected function getContext()
    {
        return array_merge(
            $this->getContextDefaults(),
            $this->extraContext
        );
    }

    /**
     * @return array
     */
    protected function getContextDefaults()
    {
        return [
            'currentUser' => $this->getMember(),
        ];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addContext($key, $value)
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
     * @param QueryResultInterface $executionResult
     * @return array
     */
    public function serialiseResult($executionResult)
    {
        // Format object
        if (!empty($executionResult->getErrors())) {
            return [
                'data' => $executionResult->getData(),
                'errors' => array_map($this->errorFormatter, $executionResult->getErrors()),
            ];
        } else {
            return [
                'data' => $executionResult->getData(),
            ];
        }
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function regenerate()
    {
        $schemas = $this->config()->get('schemas');
        $config = isset($schemas[$this->getSchemaKey()]) ? $schemas[$this->getSchemaKey()] : [];
        $this->applyConfig($config);
        $this->getSchemaStore()->persist(
            $this->types,
            $this->queries,
            $this->mutations
        );
    }
}
