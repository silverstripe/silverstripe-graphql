<?php

namespace SilverStripe\GraphQL\Interfaces;

use GraphQL\Type\Definition\Type;

interface TypeStoreInterface
{
    /**
     * @param Type $type
     * @param string $name
     * @return $this
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
     * @return $this
     */
    public function initialise();

    /**
     * @param array $types
     * @return $this
     */
    public function addTypes($types);
}