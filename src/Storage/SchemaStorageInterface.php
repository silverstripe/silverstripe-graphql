<?php

namespace SilverStripe\GraphQL\Storage;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\SchemaConfig;

interface SchemaStorageInterface
{
    /**
     * @param SchemaConfig $config
     * @param Type[] $types
     * @return $this
     */
    public function persist(SchemaConfig $config, array $types);

    /**
     * @param SchemaConfig $config
     * @return $this
     */
    public function loadIntoConfig(SchemaConfig $config);

    /**
     * @return bool
     */
    public function exists();
}