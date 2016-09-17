<?php
namespace SilverStripe\GraphQL;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use GraphQL\Schema;
use GraphQL\GraphQL;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\TypeCreator;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Error;

class Manager
{

    /**
     * @var array {@link SilverStripe\GraphQL\TypeCreator}
     */
    protected $types = [];

    /**
     * @var array Map of {@link SilverStripe\GraphQL\QueryCreator}
     */
    protected $queries = [];

    /**
     * @var callable
     */
    protected $errorFormatter = [Manager::class, 'formatError'];

    public function __construct($config = null)
    {
        if($config && array_key_exists('types', $config)) {
            foreach($config['types'] as $name => $type) {
                $this->addType($type, $name);
            }
        }

        if($config && array_key_exists('queries', $config)) {
            foreach($config['queries'] as $name => $query) {
                $this->addQuery($query, $name);
            }
        }
    }

    /**
     * @return Schema
     */
    public function schema()
    {
        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => array_map(function($query) {
                return $query->toArray();
            }, $this->queries),
        ]);

        return new Schema([
            'query' => $queryType,
        ]);
    }

    /**
     * @param string $query
     * @param array $params
     * @param null $schema
     * @return array
     */
    public function query($query, $params = [], $schema = null)
    {
        $executionResult = $this->queryAndReturnResult($query, $params, $schema);

        if (!empty($executionResult->errors))
        {
            return [
                'data' => $executionResult->data,
                'errors' => array_map($this->errorFormatter, $executionResult->errors)
            ];
        }
        else
        {
            return [
                'data' => $executionResult->data
            ];
        }
    }

    public function queryAndReturnResult($query, $params = [], $schema = null)
    {
        $schema = $this->schema($schema);
        $result = GraphQL::executeAndReturnResult($schema, $query, null, $params);
        return $result;
    }

    /**
     * @param string|Type $type An instance of {@link SilverStripe\GraphQL\TypeCreator} (or a class name)
     * @param string $name An optional identifier for this type (defaults to class name)
     */
    public function addType($type, $name = "")
    {
        if(!$name) {
            $name = is_object($type) ? get_class($type) : $type;
        }

        if(!is_object($type)) {
            $type = Injector::inst()->get($type);
        }

        if(!($type instanceof TypeCreator)) {
            throw new InvalidArgumentException(sprintf(
                'The type named "%s" needs to be a class name or instance of SilverStripe\GraphQL\TypeCreator',
                $name
            ));
        }

        $this->types[$name] = $type;
    }

    /**
     * @param string $name
     * @return TypeCreator
     */
    public function getType($name)
    {
        return $this->types[$name];
    }

    /**
     * @param string|Type $query An instance of {@link SilverStripe\GraphQL\QueryCreator} (or a class name)
     * @param string $name An optional identifier for this type (defaults to class name)
     */
    public function addQuery($query, $name = "")
    {
        if(!$name) {
            $name = is_object($query) ? get_class($query) : $query;
        }

        if(!is_object($query)) {
            $query = Injector::inst()->create($query, $this->types);
        }

        if(!($query instanceof QueryCreator)) {
            throw new InvalidArgumentException(sprintf(
                'The type named "%s" needs to be a class name or instance of SilverStripe\GraphQL\QueryCreator',
                $name
            ));
        }

        $this->queries[$name] = $query;
    }

    /**
     * @param string $name
     * @return Type
     */
    public function getQuery($name)
    {
        return $this->queries[$name];
    }

    /**
     * More verbose error display defaults
     *
     * @param Error $e
     * @return array
     */
    public static function formatError(Error $e)
    {
        $error = [
            'message' => $e->getMessage()
        ];

        $locations = $e->getLocations();
        if(!empty($locations))
        {
            $error['locations'] = array_map(function($loc)
            {
                return $loc->toArray();
            }, $locations);
        }

        $previous = $e->getPrevious();
        if($previous && $previous instanceof ValidationError)
        {
            $error['validation'] = $previous->getValidatorMessages();
        }

        return $error;
    }

}
