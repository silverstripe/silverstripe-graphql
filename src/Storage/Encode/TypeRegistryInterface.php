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
    public function get($name);

    /**
     * @param $name
     * @return bool
     */
    public function has($name);
}