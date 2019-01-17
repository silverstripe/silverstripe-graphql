<?php

namespace SilverStripe\GraphQL\Storage;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\SchemaConfig;
use SilverStripe\GraphQL\TypeAbstractions\FieldAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\SchemaAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;

interface SchemaStorageInterface
{
    /**
     * @param SchemaConfig $config
     * @param TypeAbstraction[] $types
     * @param FieldAbstraction[] $queries
     * @param FieldAbstraction[] $mutations
     * @return $this
     */
    public function persist(array $types, $queries = [], $mutations = []);

    /**
     * @return SchemaAbstraction
     */
    public function load();

    /**
     * @return bool
     */
    public function exists();
}