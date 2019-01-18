<?php

namespace SilverStripe\GraphQL\Schema\Encoding\Interfaces;

interface TypeRegistryInterface
{
    /**
     * @param string $name
     * @return mixed
     */
    public function getType($name);

    /**
     * @param $name
     * @return bool
     */
    public function hasType($name);
}
