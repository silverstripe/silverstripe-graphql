<?php

namespace SilverStripe\GraphQL\Scaffolding\Util;

use GraphQL\Type\Definition\Type;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

/**
 * Parses a type, e.g. String!=20 into an array defining the arg type
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
        if (!preg_match('/^([A-Za-z]+)(!?)(?:\s*=\s*(.*))?/', $rawArg, $matches)) {
            throw new InvalidArgumentException(
                "Invalid argument: $rawArg"
            );
        }

        $this->rawArg = $rawArg;
        $this->argType = $matches[1];
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
        $defaultValue = null;
        switch ($this->argType) {
            case Type::ID:
                $type = Type::id();
                $defaultValue = (int) $this->defaultValue;
                break;
            case Type::STRING:
                $type = Type::string();
                $defaultValue = (string) $this->defaultValue;
                break;
            case Type::BOOLEAN:
                $type = Type::boolean();
                $defaultValue = (boolean) $this->defaultValue;
                break;
            case Type::INT:
                $type = Type::int();
                $defaultValue = (int) $this->defaultValue;
                break;
            case Type::FLOAT:
                $type = Type::float();
                $defaultValue = (float) $this->defaultValue;
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
            'defaultValue' => $defaultValue
        ];
    }
}
