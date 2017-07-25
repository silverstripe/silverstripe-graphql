<?php

namespace SilverStripe\GraphQL\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\GraphQL\Manager;
use SilverStripe\Security\Security;

class SessionAuthenticationMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        /** @var \SilverStripe\Security\Member $member */
        if ($member = Security::getCurrentUser()) {
            Manager::singleton()->setMember($member);
        }
        return $delegate($request);
    }
}
