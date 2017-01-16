<?php

namespace SilverStripe\GraphQL\Auth;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;

/**
 * SilverStripe default member authenticator
 */
class MemberAuthenticator implements AuthenticatorInterface
{
    public function authenticate(HTTPRequest $request)
    {
        return Member::currentUser();
    }
}
