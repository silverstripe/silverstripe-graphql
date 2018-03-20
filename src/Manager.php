<?php

namespace SilverStripe\GraphQL;

use InvalidArgumentException;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\SourceLocation;
use GraphQL\Schema;
use GraphQL\GraphQL;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\Injectable;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use Closure;
use SilverStripe\Security\Security;

/**
 * Manager is the master container for a graphql endpoint, and contains
 * all queries, mutations, and types.
 *
 * Instantiate with {@see Manager::createFromConfig()} with a config array.
 */
class Manager
{
    use Injectable;

    const QUERY_ROOT = 'query';

    const MUTATION_ROOT = 'mutation';

    const TYPES_ROOT = 'types';

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
     * @param array $config An array with optional 'types' and 'queries' keys
     * @return Manager
     */
    public static function createFromConfig($config)
    {
        // Bootstrap schema class mapping from config
        if ($config && array_key_exists('typeNames', $config)) {
            StaticSchema::inst()->setTypeNames($config['typeNames']);
        }

        /** @var Manager $manager */
        $manager = Injector::inst()->create(Manager::class);

        // Types (incl. Interfaces and InputTypes)
        if ($config && array_key_exists('types', $config)) {
            foreach ($config['types'] as $name => $typeCreatorClass) {
                $typeCreator = Injector::inst()->create($typeCreatorClass, $manager);
                if (!($typeCreator instanceof TypeCreator)) {
                    throw new InvalidArgumentException(sprintf(
                        'The type named "%s" needs to be a class extending ' . TypeCreator::class,
                        $name
                    ));
                }

                $type = $typeCreator->toType();
                $manager->addType($type, $name);
            }
        }

        // Queries
        if ($config && array_key_exists('queries', $config)) {
            foreach ($config['queries'] as $name => $queryCreatorClass) {
                $queryCreator = Injector::inst()->create($queryCreatorClass, $manager);
                if (!($queryCreator instanceof QueryCreator)) {
                    throw new InvalidArgumentException(sprintf(
                        'The type named "%s" needs to be a class extending ' . QueryCreator::class,
                        $name
                    ));
                }

                $manager->addQuery(function () use ($queryCreator) {
                    return $queryCreator->toArray();
                }, $name);
            }
        }

        // Mutations
        if ($config && array_key_exists('mutations', $config)) {
            foreach ($config['mutations'] as $name => $mutationCreatorClass) {
                $mutationCreator = Injector::inst()->create($mutationCreatorClass, $manager);
                if (!($mutationCreator instanceof MutationCreator)) {
                    throw new InvalidArgumentException(sprintf(
                        'The mutation named "%s" needs to be a class extending ' . MutationCreator::class,
                        $name
                    ));
                }

                $manager->addMutation(function () use ($mutationCreator) {
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
                $scaffolder = $provider->provideGraphQLScaffolding($scaffolder);
            }
        }
        $scaffolder->addToManager($manager);


        return $manager;
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
     * Execute an arbitrary operation (mutation / query) on this schema
     *
     * @param string $query Raw query
     * @param array $params List of arguments given for this operation
     * @return array
     */
    public function query($query, $params = [])
    {
        $executionResult = $this->queryAndReturnResult($query, $params);

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
     * @param string $query
     * @param array $params
     * @return ExecutionResult
     */
    public function queryAndReturnResult($query, $params = [])
    {
        $schema = $this->schema();
        $context = $this->getContext();
        $result = GraphQL::executeAndReturnResult($schema, $query, null, $context, $params);

        return $result;
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
        return [
            'currentUser' => $this->getMember()
        ];
    }
}
