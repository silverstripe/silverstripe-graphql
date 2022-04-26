<?php

namespace SilverStripe\GraphQL\Auth;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * SilverStripe default member authenticator
 *
 * @internal Experimental API
 *
 * In most configurations, this will retrieve the current user from the session data.
 * This means that client needs to send the session cookie to the server, which means
 * that if it's a client session
 *
 * Outside of access by the CMS, this is unlikely to be the best authenticator, and
 * it's likely to be replaced in a future alpha/beta release
 */
class MemberAuthenticator implements AuthenticatorInterface
{
    public function authenticate(HTTPRequest $request): ?Member
    {
        return Security::getCurrentUser();
    }

    public function isApplicable(HTTPRequest $request): bool
    {
        $user = Security::getCurrentUser();
        return !empty($user);
    }
}
