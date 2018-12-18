<?php

namespace SilverStripe\GraphQL\Interfaces;

use GraphQL\Type\Definition\Type;

interface TypeStoreInterface
{
    /**
     * @param Type $type
     * @param string $name
     * @return void
     */
    public function addType(Type $type, $name = null);

    /**
     * @param string $name
     * @return Type|null
     */
    public function getType($name);

    /**
     * @param string $name
     * @return bool
     */
    public function hasType($name);

    /**
     * @return array
     */
    public function getAll();

    /**
     * @param array $types
     * @return mixed
     */
    public function initialise($types);
}