<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\Auth\AuthenticatorInterface;
use SilverStripe\Security\Member;

class BrutalAuthenticatorFake implements AuthenticatorInterface, TestOnly
{
    public function authenticate(HTTPRequest $request)
    {
        return false;
    }
}
