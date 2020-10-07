<?php


namespace SilverStripe\GraphQL\PersistedQuery;

/**
 * Implementations of query persistence must use this interface. At a minimum, they
 * must be able to fetch a query given an ID.
 */
interface PersistedQueryProvider
{
    /**
     * @param string $id
     * @return string|null
     */
    public function getQueryFromPersistedID(string $id): ?string;
}
