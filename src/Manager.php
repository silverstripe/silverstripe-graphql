<?php

namespace SilverStripe\GraphQL;

use BadMethodCallException;
use Closure;
use Exception;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use GraphQL\Language\SourceLocation;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use InvalidArgumentException;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\Middleware\Middleware;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

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
     * @var Member
     */
    protected $member;



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
        $cacheFilename = BASE_PATH . '/cached_schema.php';

        if (!file_exists($cacheFilename)) {
            $document = Parser::parse(file_get_contents(BASE_PATH . '/schema.graphql'));
            file_put_contents(
                $cacheFilename,
                "<?php\nreturn " . var_export(AST::toArray($document), true) . ";\n"
            );
        } else {
            $document = AST::fromArray(require $cacheFilename); // fromArray() is a lazy operation as well
        }
        $func = function () {
            return SiteTree::get()->first();
        };
        $typeConfigDecorator = function ($typeConfig) use ($func) {
            $typeConfig['resolve'] = $func;

            return $typeConfig;
        };
        $schema = BuildSchema::build($document, $typeConfigDecorator);

        //$schema = $this->schema();
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




}
