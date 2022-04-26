<?php

namespace SilverStripe\GraphQL\PersistedQuery;

interface PersistedQueryMappingProvider
{
    /**
     * return a map from <id> to <query>
     */
    public function getQueryMapping(string $schemaKey = 'default'): array;

    /**
     * return a query given an ID
     */
    public function getByID(string $queryID, string $schemaKey = 'default'): ?string;

    /**
     * Sets mapping of query mapping to schema keys
     */
    public function setSchemaMapping(array $mapping): self;

    public function getSchemaMapping(): array;
}
