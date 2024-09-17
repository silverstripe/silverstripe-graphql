<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\Auth\AuthenticatorInterface;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Security\Member;

class BrutalAuthenticatorFake implements AuthenticatorInterface, TestOnly
{
    public function authenticate(HTTPRequest $request): ?Member
    {
        throw new ValidationException('Never!');
    }

    public function isApplicable(HTTPRequest $request): bool
    {
        return true;
    }
}
