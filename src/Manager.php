<?php

namespace SilverStripe\GraphQL;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\SchemaConfig;
use InvalidArgumentException;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\SourceLocation;
use GraphQL\Type\Schema;
use GraphQL\GraphQL;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\Injectable;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Middleware\QueryMiddleware;
use SilverStripe\GraphQL\Serialisation\SerialisableObjectType;
use SilverStripe\GraphQL\Serialisation\TypeStoreConsumer;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use Closure;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheException;
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

    const SCHEMA_CACHE_KEY = 'schema';

    private static $dependencies = [
        'typeStore' => '%$' . TypeStoreInterface::class,
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
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var SchemaConfig
     */
    protected $schemaConfig;

    /**
     * @var TypeStoreInterface
     */
    protected $typeStore;

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
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param CacheInterface $cache
     * @return $this
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;

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
     * @param TypeStoreInterface $typeStore
     * @return $this
     */
    public function setTypeStore(TypeStoreInterface $typeStore)
    {
        $this->typeStore = $typeStore;

        return $this;
    }

    /**
     * @return TypeStoreInterface
     */
    public function getTypeStore()
    {
        return $this->typeStore;
    }

    /**
     * @param SchemaConfig $schemaConfig
     * @return $this
     */
    public function setSchemaConfig(SchemaConfig $schemaConfig)
    {
        $this->schemaConfig = $schemaConfig;

        return $this;
    }

    /**
     * @return SchemaConfig
     */
    public function getSchemaConfig()
    {
        return $this->schemaConfig;
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
     * Applies a configuration based on the schemaKey property
     *
     * @param bool $regenerate
     * @return Manager
     * @throws Exception
     * @throws CacheException
     * @throws NotFoundExceptionInterface
     */
    public function build($regenerate = false)
    {
        if (!$this->getSchemaKey()) {
            throw new BadMethodCallException(sprintf(
                'Attempted to run build() on a %s instance without a schema key set. See setSchemaKey(),
                or specify one in the constructor.',
                __CLASS__
            ));
        }
        $cache = BASE_PATH . '/schema.inc.php';
        if (!file_exists($cache) || $regenerate) {
            $this->regenerate();
        }

        include($cache);

        $className = $this->getSchemaKey() . '_' . md5($this->getSchemaKey());
        $registry = new $className();
        $schemaConfig = $this->createSchemaConfig();
        $schemaConfig->setTypeLoader(function ($type) use ($registry) {
            return $registry->get($type);
        });
        $schemaConfig->setQuery($registry->get('Query'));
        $schemaConfig->setMutation($registry->get('Mutation'));
        $this->setSchemaConfig($schemaConfig);

        return $this;
    }

    /**
     * @deprecated 4.0 Use Manager::build() instead
     */
    public function configure()
    {
        Deprecation::notice(
            '4.0',
            sprintf(
                '%s::%s is deprecated. Use %s::build() instead',
                __CLASS__,
                __FUNCTION__,
                __CLASS__
            )
        );
    }

    /**
     * @param array $config An array with optional 'types' and 'queries' keys
     * @return Manager
     * @throws NotFoundExceptionInterface
     * @throws Error
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
     * Build the main Schema instance that represents the final schema for this endpoint
     *
     * @return Schema
     */
    public function schema()
    {
        if (!$this->schemaConfig) {
            throw new BadMethodCallException(sprintf(
                'No schema config available. Did you call %s before build()?',
                __FUNCTION__
            ));
        }
        return new Schema($this->schemaConfig);
    }

    /**
     * @return SchemaConfig
     */
    protected function createSchemaConfig()
    {
        $config = new SchemaConfig();
        if (!empty($this->queries)) {
            $config->setQuery(new SerialisableObjectType([
                'name' => 'Query',
                'fields' => array_map(function ($query) {
                    return is_callable($query) ? $query() : $query;
                }, $this->queries),
            ]));
        }

        if (!empty($this->mutations)) {
            $config->setMutation(new SerialisableObjectType([
                'name' => 'Mutation',
                'fields' => array_map(function ($mutation) {
                    return is_callable($mutation) ? $mutation() : $mutation;
                }, $this->mutations),
            ]));
        }

        return $config;
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
            return GraphQL::executeQuery($schema, $query, null, $context, $params);
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
        $this->getTypeStore()->addType($type, $name);
    }

    /**
     * Return a type definition by name
     *
     * @param string $name
     * @return Type
     */
    public function getType($name)
    {
        $type = $this->getTypeStore()->getType($name);
        if (!$type) {
            throw new InvalidArgumentException("Type '$name' is not a registered GraphQL type");
        }

        return $type;
    }

    /**
     * @param  string $name
     *
     * @return boolean
     */
    public function hasType($name)
    {
        return $this->getTypeStore()->hasType($name);
    }

    /**
     * Register a new Query. Query can be defined as a closure to ensure
     * dependent types are lazy loaded.
     *
     * @param array|FieldDefinition|Closure $query
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
     * @param array|FieldDefinition|Closure $mutation
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
     * @throws Error
     * @throws NotFoundExceptionInterface
     */
    protected function bootConfig()
    {
        $schemas = $this->config()->get('schemas');
        $config = isset($schemas[$this->getSchemaKey()]) ? $schemas[$this->getSchemaKey()] : [];
        $this->applyConfig($config);
    }

    /**
     * @throws CacheException
     * @throws Error
     * @throws NotFoundExceptionInterface
     */
    public function regenerate()
    {
        $this->bootConfig();
        $schemaConfig = $this->createSchemaConfig();
        $methods = [];
        $types = $this->getTypeStore()->getAll();
        $types[] = $schemaConfig->getQuery();
        $types[] = $schemaConfig->getMutation();
        foreach($types as $type) {
            $methods[] = <<<PHP
private function {$type->name}()
{
    return {$type->toCode()};
}
PHP;
            }
            $hash = md5($this->getSchemaKey());
            $functions = implode("\n\n", $methods);
$code = <<<PHP
<?php
use GraphQL\Type\Definition\Type;

class {$this->getSchemaKey()}_{$hash}
{
  private \$types = [];
  
  public function get(\$name)
  {
        if (!isset(\$this->types[\$name])) {
            \$this->types[\$name] = \$this->{\$name}();
        }
        return \$this->types[\$name];
  }
  
  {$functions}
}
PHP;
file_put_contents(BASE_PATH . '/schema.inc.php', $code);

    }
}
