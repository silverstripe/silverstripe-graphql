<?php

namespace SilverStripe\GraphQL\Scaffolding\Util;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Interfaces\TypeParserInterface;
use SilverStripe\Core\Injector\Injectable;

/**
 * Parses a type, e.g. Int!(20) into an array defining the arg type
 */
class StringTypeParser implements TypeParserInterface
{
    use Injectable;

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
     * Returns true if the given type is an internal GraphQL type, e.g. "String" or "Int"
     *
     * @param  $type
     * @return bool
     */
    public static function isInternalType($type)
    {
        $types = array_keys(Type::getInternalTypes());

        return in_array($type, $types);
    }

    /**
     * TypeParser constructor.
     *
     * @param string $rawArg
     */
    public function __construct($rawArg)
    {
        if (!is_string($rawArg)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s::__construct() must be passed a string',
                    __CLASS__
                )
            );
        }

        if (!preg_match('/^([A-Za-z]+)(!?)(?:\s*\(\s*(.*)\))?/', $rawArg, $matches)) {
            throw new InvalidArgumentException(
                "Invalid argument: $rawArg"
            );
        }

        if (!static::isInternalType($matches[1])) {
            throw new InvalidArgumentException("Invalid type: " . $matches[1]);
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
    public function getName()
    {
        return $this->typeStr;
    }

    /**
     * @return null
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue === null) {
            return null;
        }

        switch ($this->typeStr) {
            case Type::ID:
                return (int)$this->defaultValue;
            case Type::STRING:
                return (string)$this->defaultValue;
            case Type::BOOLEAN:
                return (boolean)$this->defaultValue;
            case Type::INT:
                return (int)$this->defaultValue;
            case Type::FLOAT:
                return (float)$this->defaultValue;
            default:
                return null;
        }
    }

    /**
     * @return Type
     */
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
            default:
                return null;
        }
    }
}
