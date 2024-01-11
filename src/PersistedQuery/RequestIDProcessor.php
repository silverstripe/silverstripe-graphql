<?php

namespace SilverStripe\GraphQL\PersistedQuery;

use LogicException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider;

class RequestIDProcessor implements RequestProcessor
{
    /**
     * Parse query and variables from the given request
     *
     * @param HTTPRequest $request
     * @return array Array containing query and variables as a pair
     * @throws LogicException
     */
    public function getRequestQueryVariables(HTTPRequest $request): array
    {
        $query = $request->requestVar('query');
        $id = $request->requestVar('id');
        $variables = json_decode($request->requestVar('variables') ?? '', true);

        if ($id) {
            if ($query) {
                throw new LogicException('Cannot pass a query when an ID has been specified.');
            }
            $provider = Injector::inst()->get(PersistedQueryMappingProvider::class);

            $query = $provider->getByID($id);
        }

        return [$query, $variables];
    }
}
