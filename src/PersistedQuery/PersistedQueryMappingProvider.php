<?php

namespace SilverStripe\GraphQL\PersistedQuery;

/**
 * Interface PersistedQueryMappingProvider
 * @package SilverStripe\GraphQL\PersistedQuery
 */
interface PersistedQueryMappingProvider
{
    /**
     * return a map from <id> to <query>
     *
     * @param string $schemaKey
     * @return array
     */
    public function getQueryMapping($schemaKey = 'default');

    /**
     * return a query given an ID
     *
     * @param string $queryID
     * @param string $schemaKey
     * @return string
     */
    public function getByID($queryID, $schemaKey = 'default');

    /**
     * Sets mapping of query mapping to schema keys
     *
     * @param array $mapping
     * @return mixed
     */
    public function setSchemaMapping(array $mapping);

    /**
     * @return array
     */
    public function getSchemaMapping();
}
