<?php

namespace SilverStripe\GraphQL\Schema;

use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\Schema;
use SilverStripe\GraphQL\Schema\Components\AbstractType;

interface SchemaStorageInterface
{
    /**
     * @param AbstractType[] $types
     * @param Field[] $queries
     * @param \SilverStripe\GraphQL\Schema\Components\Field[] $mutations
     * @return $this
     */
    public function persist(array $types, $queries = [], $mutations = []);

    /**
     * @return Schema
     */
    public function load();

    /**
     * @return bool
     */
    public function exists();
}
