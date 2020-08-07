<?php

namespace SilverStripe\GraphQL\Extensions;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;

/**
 * Class IntrospectionProvider
 */
class IntrospectionProvider extends Extension
{
    private static $allowed_actions = [
        'types'
    ];

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function types(HTTPRequest $request)
    {
        $fragments = $this->owner->introspectTypes();

        return (new HTTPResponse(json_encode($fragments), 200))
            ->addHeader('Content-Type', 'application/json');
    }
}
