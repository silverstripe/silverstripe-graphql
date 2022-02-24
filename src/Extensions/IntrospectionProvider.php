<?php

namespace SilverStripe\GraphQL\Extensions;

use Exception;
use SilverStripe\Core\Extension;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
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
        try {
            $manager = $this->owner->getManager();
        } catch (Exception $ex) {
            return (new HTTPResponse(json_encode(["error" => "Server error"]), 500))
                ->addHeader('Content-Type', 'application/json');
        }

        $fragments = StaticSchema::inst()->introspectTypes($manager);

        return (new HTTPResponse(json_encode($fragments), 200))
            ->addHeader('Content-Type', 'application/json');
    }
}
