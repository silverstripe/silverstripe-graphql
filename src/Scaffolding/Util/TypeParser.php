<?php

namespace SilverStripe\GraphQL\Scaffolding\Util;

use GraphQL\Type\Definition\Type;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

/**
 * Parses a type, e.g. Int!(20) into an array defining the arg type
 */
class TypeParser
{

    /**
     * @var string
     */
    protected $rawArg;

    /**
     * @var string
     */
    protected $typeStr;

    /**
     * @var bool
     */
    protected $required = false;

    /**
     * @var null
     */
    protected $defaultValue = null;
    
    /**
     * TypeParser constructor.
     * @param $rawArg
     */
    public function __construct($rawArg)
    {
        if (!preg_match('/^([A-Za-z]+)(!?)(?:\s*\(\s*(.*)\))?/', $rawArg, $matches)) {
            throw new InvalidArgumentException(
                "Invalid argument: $rawArg"
            );
        }

        $this->rawArg = $rawArg;
        $this->typeStr = $matches[1];
        $this->required = isset($matches[2]) && $matches[2] == '!';
        if (isset($matches[3])) {
            $this->defaultValue = $matches[3];
        }

    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return mixed
     */
    public function getArgTypeName()
    {
        return $this->typeStr;
    }

    /**
     * @return null
     */
    public function getDefaultValue()
    {
        if($this->defaultValue === null) {
        	return null;
        }
        
        switch ($this->typeStr) {
            case Type::ID:                
                return (int) $this->defaultValue;                
            case Type::STRING:
                return (string) $this->defaultValue;                
            case Type::BOOLEAN:                
                return (boolean) $this->defaultValue;                
            case Type::INT:                
                return (int) $this->defaultValue;                
            case Type::FLOAT:                
                return (float) $this->defaultValue;               
        }

        throw new InvalidArgumentException(sprintf(
        	'Invalid type %s',
        	$this->typeStr
        ));
    }

    public function getType()
    {
        switch ($this->typeStr) {
            case Type::ID:
                return Type::id();
            case Type::STRING:
                return Type::string();
            case Type::BOOLEAN:
                return Type::boolean();
            case Type::INT:
                return Type::int();
            case Type::FLOAT:
                return Type::float();
        }    	
    }

}
