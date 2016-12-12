<?php

namespace SilverStripe\GraphQL\Scaffolding\Util;

use GraphQL\Type\Definition\Type;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

/**
 * Parses a type, e.g. String!=20 into an array defining the arg type
 * @package SilverStripe\GraphQL\Scaffolding\Util
 */
class TypeParser
{

    /**
     * @var strign
     */
    protected $rawArg;

    /**
     * @var string
     */
    protected $argType;

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
        if (!preg_match('/^([A-Za-z]+)(!?)(\s*=\s*(.*))?/', $rawArg, $matches)) {
            throw new InvalidArgumentException(
                "Invalid argument: $rawArg"
            );
        }

        $this->rawArg = $rawArg;
        $this->argType = $matches[1];
        $this->required = isset($matches[2]) && $matches[2] == '!';
        if (isset($matches[4])) {
            $this->defaultValue = $matches[4];
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
        return $this->argType;
    }

    /**
     * @return null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $type = null;
        switch ($this->argType) {
            case Type::ID:
                $type = Type::id();
                break;
            case Type::STRING:
                $type = Type::string();
                break;
            case Type::BOOLEAN:
                $type = Type::boolean();
                break;
            case Type::INT:
                $type = Type::int();
                break;
            case Type::FLOAT:
                $type = Type::float();
                break;

        }

        if ($this->required) {
            $type = Type::nonNull($type);
        }

        if (!$type) {
            throw new InvalidArgumentException("Invalid GraphQL type: $this->argType");
        }

        return [
            'type' => $type,
            'defaultValue' => $this->defaultValue
        ];
    }
}