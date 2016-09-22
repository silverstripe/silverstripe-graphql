<?php

namespace Chillu\GraphQL;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use GraphQL\Schema;
use GraphQL\GraphQL;
use SilverStripe\Core\Injector\Injector;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Error;
use GraphQL\Type\Definition\Type;

class Manager
{
    /**
     * @var array {@link Chillu\GraphQL\TypeCreator}
     */
    protected $types = [];

    /**
     * @var array Map of {@link Chillu\GraphQL\QueryCreator}
     */
    protected $queries = [];

    /**
     * @var callable
     */
    protected $errorFormatter = [self::class, 'formatError'];

    /**
     * @param array $config An array with optional 'types' and 'queries' keys
     * @return Manager
     */
    public static function createFromConfig($config)
    {
        $manager = Injector::inst()->create(Manager::class);
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

        if ($config && array_key_exists('queries', $config)) {
            foreach ($config['queries'] as $name => $queryCreatorClass) {
                $queryCreator = Injector::inst()->create($queryCreatorClass, $manager);
                if (!($queryCreator instanceof QueryCreator)) {
                    throw new InvalidArgumentException(sprintf(
                        'The type named "%s" needs to be a class extending ' . QueryCreator::class,
                        $name
                    ));
                }

                $query = $queryCreator->toArray();
                $manager->addQuery($query, $name);
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

        return new Schema([
            'query' => $queryType,
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
        $result = GraphQL::executeAndReturnResult($schema, $query, null, $params);

        return $result;
    }

    /**
     * @param Type   $type
     * @param string $name An optional identifier for this type (defaults to 'name' attribute in type definition).
     *                     Needs to be unique in schema.
     */
    public function addType(Type $type, $name = '')
    {
        if(!$name) {
            $name = (string)$type;
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
        return $this->types[$name];
    }

    /**
     * @param array  $query
     * @param string $name Identifier for this query (unique in schema)
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
}
