<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\Auth\AuthenticatorInterface;
use SilverStripe\Security\Member;

class PushoverAuthenticatorFake implements AuthenticatorInterface, TestOnly
{
    public function authenticate(HTTPRequest $request)
    {
        return Member::create(['Email' => 'john@example.com']);
    }

    public function isApplicable(HTTPRequest $request)
    {
        return true;
    }
}
