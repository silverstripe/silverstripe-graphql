<?php

namespace SilverStripe\GraphQL\Auth;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\BasicAuth;

/**
 * An authenticator using SilverStripe's BasicAuth
 */
class BasicAuthAuthenticator implements AuthenticatorInterface
{
    public function authenticate(HTTPRequest $request)
    {
        try {
            return BasicAuth::requireLogin($request, 'Restricted resource');
        } catch (HTTPResponse_Exception $ex) {
            // BasicAuth::requireLogin may throw its own exception with an HTTPResponse in it
            $failureMessage = (string) $ex->getResponse()->getBody();
            throw new ValidationException($failureMessage, 401);
        }
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
     * @param  string $servervar
     * @return bool
     */
    protected function hasAuthHandler($servervar)
    {
        return isset($_SERVER[$servervar]) && preg_match('/Basic\s+(.*)$/i', $_SERVER[$servervar]);
    }
}
