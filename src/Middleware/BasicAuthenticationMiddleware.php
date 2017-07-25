<?php

namespace SilverStripe\GraphQL\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\GraphQL\Manager;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\BasicAuth;

class BasicAuthenticationMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        try {
            if ($member = BasicAuth::requireLogin($request, 'Restricted resource')) {
                Manager::singleton()->setMember($member);
            }
        } catch (HTTPResponse_Exception $ex) {
            // BasicAuth::requireLogin may throw its own exception with an HTTPResponse in it
            $failureMessage = (string) $ex->getResponse()->getBody();
            throw new ValidationException($failureMessage, 401);
        } finally {
            return $delegate($request);
        }
    }
}
