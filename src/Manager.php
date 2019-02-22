<?php

namespace SilverStripe\GraphQL;

use InvalidArgumentException;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\SourceLocation;
use GraphQL\Schema;
use GraphQL\GraphQL;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\Injectable;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Middleware\QueryMiddleware;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use Closure;
use SilverStripe\Security\Security;
use BadMethodCallException;
use Exception;

/**
 * Manager is the master container for a graphql endpoint, and contains
 * all queries, mutations, and types.
 *
 * Instantiate with {@see Manager::createFromConfig()} with a config array.
 */
class Manager implements ConfigurationApplier
{
    use Injectable;
    use Extensible;
    use Configurable;

    const QUERY_ROOT = 'query';

    const MUTATION_ROOT = 'mutation';

    const TYPES_ROOT = 'types';

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
     * @param string $schemaKey
     */
    public function __construct($schemaKey = null)
    {
        if ($schemaKey) {
            $this->setSchemaKey($schemaKey);
        }
    }

    /**
     * @param $config
     * @param string $schemaKey
     * @return Manager
     * @deprecated 4.0
     */
    public static function createFromConfig($config, $schemaKey = null)
    {
        Deprecation::notice('4.0', 'Use applyConfig() on a new instance instead');

        $manager = new static($schemaKey);

        return $manager->applyConfig($config);
    }

    /**
     * Applies a configuration based on the schemaKey property
     *
     * @return Manager
     * @throws Exception
     */
    public function configure()
    {
        if (!$this->getSchemaKey()) {
            throw new BadMethodCallException(sprintf(
                'Attempted to run configure() on a %s instance without a schema key set. See setSchemaKey(),
                or specify one in the constructor.',
                __CLASS__
            ));
        }

        $schemas = $this->config()->get('schemas');
        $config = isset($schemas[$this->getSchemaKey()]) ? $schemas[$this->getSchemaKey()] : [];

        return $this->applyConfig($config);
    }

    /**
     * @param array $config An array with optional 'types' and 'queries' keys
     * @return Manager
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

                $this->addQuery(function () use ($queryCreator) {
                    return $queryCreator->toArray();
                }, $name);
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

                $this->addMutation(function () use ($mutationCreator) {
                    return $mutationCreator->toArray();
                }, $name);
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
     * Build the main Schema instance that represents the final schema for this endpoint
     *
     * @return Schema
     */
    public function schema()
    {
        $schema = [
            // usually inferred from 'query', but required for polymorphism on InterfaceType-based query results
            self::TYPES_ROOT => $this->types,
        ];

        if (!empty($this->queries)) {
            $schema[self::QUERY_ROOT] = new ObjectType([
                'name' => 'Query',
                'fields' => function () {
                    return array_map(function ($query) {
                        return is_callable($query) ? $query() : $query;
                    }, $this->queries);
                },
            ]);
        } else {
            $schema[self::QUERY_ROOT] = new ObjectType([
                'name' => 'Query',
            ]);
        }

        if (!empty($this->mutations)) {
            $schema[self::MUTATION_ROOT] = new ObjectType([
                'name' => 'Mutation',
                'fields' => function () {
                    return array_map(function ($mutation) {
                        return is_callable($mutation) ? $mutation() : $mutation;
                    }, $this->mutations);
                },
            ]);
        } else {
            $schema[self::MUTATION_ROOT] = new ObjectType([
                'name' => 'Mutation',
            ]);
        }

        return new Schema($schema);
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
     * @return ExecutionResult|array Result as either source object result, or serialised as array.
     */
    public function queryAndReturnResult($query, $params = [])
    {
        $schema = $this->schema();
        $context = $this->getContext();

        $last = function ($schema, $query, $context, $params) {
            return GraphQL::executeAndReturnResult($schema, $query, null, $context, $params);
        };

        return $this->callMiddleware($schema, $query, $context, $params, $last);
    }

    /**
     * Register a new type
     *
     * @param Type $type
     * @param string $name An optional identifier for this type (defaults to 'name'
     * attribute in type definition). Needs to be unique in schema.
     */
    public function addType(Type $type, $name = '')
    {
        if (!$name) {
            $name = (string)$type;
        }

        $this->types[$name] = $type;
    }

    /**
     * Return a type definition by name
     *
     * @param string $name
     * @return Type
     */
    public function getType($name)
    {
        if (isset($this->types[$name])) {
            return $this->types[$name];
        } else {
            throw new InvalidArgumentException("Type '$name' is not a registered GraphQL type");
        }
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
     * @param array|Closure $query
     * @param string $name Identifier for this query (unique in schema)
     */
    public function addQuery($query, $name)
    {
        $this->queries[$name] = $query;
    }

    /**
     * Get a query by name
     *
     * @param string $name
     * @return array
     */
    public function getQuery($name)
    {
        return $this->queries[$name];
    }

    /**
     * Register a new mutation. Mutations can be callbacks to ensure
     * dependent types are lazy-loaded.
     *
     * @param array|Closure $mutation
     * @param string $name Identifier for this mutation (unique in schema)
     */
    public function addMutation($mutation, $name)
    {
        $this->mutations[$name] = $mutation;
    }

    /**
     * Get a mutation by name
     *
     * @param string $name
     * @return array
     */
    public function getMutation($name)
    {
        return $this->mutations[$name];
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
     * @param any $value
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
}
