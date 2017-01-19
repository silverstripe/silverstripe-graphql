<?php

namespace SilverStripe\GraphQL\Auth;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\BasicAuth;
use SilverStripe\Security\Member;

/**
 * An authenticator using SilverStripe's BasicAuth
 *
 * @package silverstripe-graphql
 */
class BasicAuthAuthenticator implements AuthenticatorInterface
{

    public function authenticate(HTTPRequest $request)
    {
        $member = BasicAuth::requireLogin('Restricted resource');
        if ($member instanceof Member) {
            return $member;
        }
        return null;
    }

    public function isApplicable(HTTPRequest $request)
    {
        if ($this->hasAuthHandler('HTTP_AUTHORIZATION')
            || $this->hasAuthHandler('REDIRECT_HTTP_AUTHORIZATION')
        ) {
            return true;
        }
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            return true;
        }
        return false;
    }

    /**
     * Check for $_SERVERVAR with basic auth credentials
     *
     * @param $servervar
     * @return bool
     */
    protected function hasAuthHandler($servervar)
    {
        return isset($_SERVER[$servervar]) && preg_match('/Basic\s+(.*)$/i', $_SERVER[$servervar]);
    }
}
