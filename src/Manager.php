<?php
namespace SilverStripe\GraphQL;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use GraphQL\Schema;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\TypeCreator;

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
    
}
