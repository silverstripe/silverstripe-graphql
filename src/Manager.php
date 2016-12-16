<?php

namespace SilverStripe\GraphQL;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use GraphQL\Schema;
use GraphQL\GraphQL;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\Injectable;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Error;
use GraphQL\Type\Definition\Type;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\Scaffolding\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\GraphQLScaffolder;

class Manager
{
    use Injectable;

    /**
     * @var array Map of named {@link Type}
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
     *
     * @return Manager
     */
    public static function createFromConfig($config)
    {
        /** @var Manager $manager */
        $manager = Injector::inst()->create(self::class);

        if (isset($config['scaffolding'])) {
            $scaffolder = GraphQLScaffolder::createFromConfig($config['scaffolding']);
        } else {
            $scaffolder = new GraphQLScaffolder();
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

        // Types (incl. Interfaces and InputTypes)
        if ($config && array_key_exists('types', $config)) {
            foreach ($config['types'] as $name => $typeCreatorClass) {
                $typeCreator = Injector::inst()->create($typeCreatorClass, $manager);
                if (!($typeCreator instanceof TypeCreator)) {
                    throw new InvalidArgumentException(sprintf(
                        'The type named "%s" needs to be a class extending '.TypeCreator::class,
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
                        'The type named "%s" needs to be a class extending '.QueryCreator::class,
                        $name
                    ));
                }

                $query = $queryCreator->toArray();
                $manager->addQuery($query, $name);
            }
        }

        // Mutations
        if ($config && array_key_exists('mutations', $config)) {
            foreach ($config['mutations'] as $name => $mutationCreatorClass) {
                $mutationCreator = Injector::inst()->create($mutationCreatorClass, $manager);
                if (!($mutationCreator instanceof MutationCreator)) {
                    throw new InvalidArgumentException(sprintf(
                        'The mutation named "%s" needs to be a class extending '.MutationCreator::class,
                        $name
                    ));
                }

                $mutation = $mutationCreator->toArray();
                $manager->addMutation($mutation, $name);
            }
        }

        return $manager;
    }

    /**
     * @return Schema
     */
    public function schema()
    {
        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => $this->queries,
        ]);

        $mutationType = new ObjectType([
            'name' => 'Mutation',
            'fields' => $this->mutations,
        ]);

        return new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
            // usually inferred from 'query', but required for polymorphism on InterfaceType-based query results
            'types' => $this->types,
        ]);
    }

    /**
     * @param string $query
     * @param array  $params
     * @param null   $schema
     *
     * @return array
     */
    public function query($query, $params = [], $schema = null)
    {
        $executionResult = $this->queryAndReturnResult($query, $params, $schema);

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
     * @param array  $params
     * @param null   $schema
     *
     * @return array
     */
    public function queryAndReturnResult($query, $params = [], $schema = null)
    {
        $schema = $this->schema($schema);
        $context = $this->getContext();
        $result = GraphQL::executeAndReturnResult($schema, $query, null, $context, $params);

        return $result;
    }

    /**
     * @param Type   $type
     * @param string $name An optional identifier for this type (defaults to 'name' attribute in type definition).
     *                     Needs to be unique in schema
     */
    public function addType(Type $type, $name = '')
    {
        if (!$name) {
            $name = (string) $type;
        }
        $this->types[$name] = $type;
    }

    /**
     * @param string $name
     *
     * @return Type
     */
    public function getType($name)
    {
        if (isset($this->types[$name])) {
            return $this->types[$name];
        } else {
            throw new \InvalidArgumentException("Type '$name' is not a registered GraphQL type");
        }
    }

    /**
     * @param  string  $name
     * 
     * @return boolean
     */
    public function hasType($name)
    {
    	return isset($this->types[$name]);
    }

    /**
     * @param array  $query
     * @param string $name  Identifier for this query (unique in schema)
     */
    public function addQuery($query, $name)
    {
        $this->queries[$name] = $query;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getQuery($name)
    {
        return $this->queries[$name];
    }

    /**
     * @param array  $mutation
     * @param string $name     Identifier for this mutation (unique in schema)
     */
    public function addMutation($mutation, $name)
    {
        $this->mutations[$name] = $mutation;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getMutation($name)
    {
        return $this->mutations[$name];
    }

    /**
     * More verbose error display defaults.
     *
     * @param Error $e
     *
     * @return array
     */
    public static function formatError(Error $e)
    {
        $error = [
            'message' => $e->getMessage(),
        ];

        $locations = $e->getLocations();
        if (!empty($locations)) {
            $error['locations'] = array_map(function ($loc) {
                return $loc->toArray();
            }, $locations);
        }

        $previous = $e->getPrevious();
        if ($previous && $previous instanceof ValidationError) {
            $error['validation'] = $previous->getValidatorMessages();
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
        return $this->member ?: Member::currentUser();
    }

    /**
     * @return array
     */
    protected function getContext()
    {
        return [
            'currentUser' => $this->getMember()
        ];
    }
}
