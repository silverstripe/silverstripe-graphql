<?php

namespace SilverStripe\GraphQL\Scaffolding\Util;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Interfaces\TypeParserInterface;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\TypeAbstractions\InternalType;

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
        return InternalType::exists($type);
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
            case InternalType::TYPE_ID:
                return (int)$this->defaultValue;
            case InteralType::TYPE_STRING:
                return (string)$this->defaultValue;
            case InteralType::TYPE_BOOLEAN:
                return (boolean)$this->defaultValue;
            case InteralType::TYPE_INT:
                return (int)$this->defaultValue;
            case InteralType::TYPE_FLOAT:
                return (float)$this->defaultValue;
            default:
                return $this->defaultValue;
        }
    }

    /**
     * @param boolean $nullable If true, allow the type to be null. Otherwise,
     *  return the typename, which may be arbitrary.
     * @return Type|string
     */
    public function getType($nullable = true)
    {
        switch ($this->typeStr) {
            case InternalType::TYPE_ID:
                return InternalType::id();
            case InternalType::TYPE_STRING:
                return InternalType::string();
            case InternalType::TYPE_BOOLEAN:
                return InternalType::boolean();
            case InternalType::TYPE_INT:
                return InternalType::int();
            case InternalType::TYPE_FLOAT:
                return InternalType::float();
            default:
                return $nullable ? null : $this->typeStr;
        }
    }
}
