<?php


namespace SilverStripe\GraphQL\PersistedQuery;

use SilverStripe\Control\HTTPRequest;

/**
 * Implementations of query persistence must use this interface. At a minimum, they
 * must be able to fetch a query given an ID.
 */
interface RequestProcessor
{
    /**
     * @param string $id
     * @return string|null
     */
    /**
     * Parse query and variables from the given request
     *
     * @param HTTPRequest $request
     * @return array Array containing query and variables as a pair
     */
    public function getRequestQueryVariables(HTTPRequest $request): array;
}
