<?php

namespace SilverStripe\GraphQL\Auth;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;

/**
 * The authentication Handler is responsible for handling authentication requirements and providing a Member
 * to the Manager if required, so it can be used in request contexts.
 *
 * @package silverstripe-graphql
 */
class Handler
{
    /**
     * If required, enforce authentication for non-session authenticated requests. The Member returned from the
     * authentication method will returned for use in the OperationResolver context.
     *
     * Authenticators are defined in configuration. @see AuthenticatorInterface::authenticate.
     *
     * @param  HTTPRequest $request
     * @return Member|false           If authentication was successful the Member is returned. False if no
     *                                authenticators are configured.
     * @throws HTTPResponse_Exception If authentication is attempted and fails
     */
    public function requireAuthentication(HTTPRequest $request)
    {
        $authenticator = $this->getAuthenticator();
        if (!$authenticator) {
            return false;
        }

        $member = $authenticator->authenticate($request);
        if ($member instanceof Member) {
            return $member;
        }
        // Note: The authenticator class itself may also throw an exception
        throw new HTTPResponse_Exception('Authentication failed.', 401);
    }

    /**
     * Returns the first configured authenticator by highest priority, or false if none are configured
     *
     * @return AuthenticatorInterface|false
     */
    public function getAuthenticator()
    {
        $authenticators = Config::inst()->get('SilverStripe\GraphQL', 'authenticators');
        if (empty($authenticators)) {
            return false;
        }

        $this->prioritiseAuthenticators($authenticators);

        $authenticator = false;
        foreach ($authenticators as $authenticatorConfig) {
            if (!ClassInfo::classImplements($authenticatorConfig['class'], AuthenticatorInterface::class)) {
                throw new ValidationException(
                    sprintf('%s must implement %s!', $authenticatorConfig['class'], AuthenticatorInterface::class)
                );
            }
            $authenticator = Injector::inst()->get($authenticatorConfig['class']);
            break;
        }

        return $authenticator;
    }

    /**
     * Sort the configured authenticators by their "priority" (highest to lowest). This allows modules to
     * contribute to the decision of which authenticator should be used first. Users can rewrite this in their
     * own configuration if necessary.
     *
     * @param array $authenticators
     */
    public function prioritiseAuthenticators(&$authenticators)
    {
        usort($authenticators, function ($a, $b) {
            // Set some default values
            if (!isset($a['priority'])) {
                $a['priority'] = 10;
            }
            if (!isset($b['priority'])) {
                $b['priority'] = 10;
            }

            return $a['priority'] < $b['priority'];
        });
    }
}
