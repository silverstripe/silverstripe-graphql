<?php

namespace SilverStripe\GraphQL\Auth;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\BasicAuth;

/**
 * An authenticator using SilverStripe's BasicAuth
 *
 * @package silverstripe-graphql
 */
class BasicAuthAuthenticator implements AuthenticatorInterface
{
    /**
     * {@inheritDoc}
     */
    public function authenticate(HTTPRequest $request)
    {
        return BasicAuth::requireLogin('Restricted resource');
    }
}
