<?php

namespace SilverStripe\GraphQL\Scaffolding\Util;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use GraphQL\Type\Definition\Type;

/**
 * Parses an array of args into the proper graphql-php spec, using first-class type objects in lieu of strings
 */
class ArgsParser
{

    /**
     * @var array
     */
    protected $args = [];

    /**
     * ArgsParser constructor.
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->args = $args;
    }

    /**
     * Gets the array of args suitable for a field creator
     * @return array
     */
    public function toArray()
    {
        $args = [];
        foreach ($this->args as $argName => $type) {
            if (is_string($type)) {
                $args[$argName] = (new TypeParser($type))->toArray();
            } else {
                if ($type instanceof Type) {
                    $args[$argName] = ['type' => $type];
                } else {
                    if (is_array($type)) {
                        $args[$argName] = $type;
                    } else {
                        throw new InvalidArgumentException(
                            "Invalid argument type provided for $argName"
                        );
                    }
                }
            }
        }

        return $args;
    }
}