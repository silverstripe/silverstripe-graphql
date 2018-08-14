<?php

namespace SilverStripe\GraphQL\PersistedQuery;

/**
 * Interface PersistedQueryMappingProvider
 * @package SilverStripe\GraphQL\PersistedQuery
 */
interface PersistedQueryMappingProvider
{
    /**
     * return a map from <query> to <id>
     *
     * @param string $schemaKey
     * @return array
     */
    public function getMapping($schemaKey = 'default');

    /**
     * return a map from <id> to <query>
     *
     * @param string $schemaKey
     * @return array
     */
    public function getInvertedMapping($schemaKey = 'default');
}
