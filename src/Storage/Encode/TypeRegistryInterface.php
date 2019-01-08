<?php

namespace SilverStripe\GraphQL\Storage\Encode;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

interface TypeRegistryInterface
{
    /**
     * @param string $name
     * @return Type|ObjectType|InputObjectType
     */
    public function getType($name);

    /**
     * @param $name
     * @return bool
     */
    public function hasType($name);
}