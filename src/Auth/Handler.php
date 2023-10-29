<?php

namespace SilverStripe\GraphQL\Auth;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;

/**
 * The authentication Handler is responsible for handling authentication requirements and providing a Member
 * to the Manager if required, so it can be used in request contexts.
 */
class Handler
{
    use Configurable;

    /**
     * @config
     * @var array
     *
     * @internal Experimental config
     */
    private static $authenticators = [
        [
            'class' => MemberAuthenticator::class,
            'priority' => 10,
        ]
    ];

    /**
     * If required, enforce authentication for non-session authenticated requests. The Member returned from the
     * authentication method will returned for use in the OperationResolver context.
     *
     * Authenticators are defined in configuration. @see AuthenticatorInterface::authenticate.
     *
     * @param  HTTPRequest $request
     * @return Member|false         If authentication was successful the Member is returned. False if no
     *                              authenticators are configured.
     * @throws ValidationException  If authentication is attempted and fails
     */
    public function requireAuthentication(HTTPRequest $request)
    {
        $authenticator = $this->getAuthenticator($request);
        if (!$authenticator) {
            return false;
        }

        $member = $authenticator->authenticate($request);
        if ($member instanceof Member) {
            return $member;
        }
        // Note: The authenticator class itself may also throw an exception when called
        throw new ValidationException('Authentication failed.', 401);
    }

    /**
     * Returns the first configured authenticator by highest priority, or null if none are configured
     *
     * @param HTTPRequest $request
     * @return null|AuthenticatorInterface
     */
    public function getAuthenticator(HTTPRequest $request): ?AuthenticatorInterface
    {
        // Get list of default authenticators
        $authenticators = $this->config()->get('authenticators');
        if (empty($authenticators)) {
            return null;
        }

        // Build authenticator from first class
        $this->prioritiseAuthenticators($authenticators);
        foreach ($authenticators as $authenticatorConfig) {
            $authenticator = $this->buildAuthenticator($authenticatorConfig['class']);
            if ($authenticator->isApplicable($request)) {
                return $authenticator;
            }
        }
        return null;
    }

    /**
     * @param string $authenticator
     * @return AuthenticatorInterface
     * @throws ValidationException
     */
    protected function buildAuthenticator(string $authenticator): AuthenticatorInterface
    {
        if (!ClassInfo::classImplements($authenticator, AuthenticatorInterface::class)) {
            throw new ValidationException(
                sprintf('%s must implement %s!', $authenticator, AuthenticatorInterface::class)
            );
        }
        return Injector::inst()->get($authenticator);
    }

    /**
     * Sort the configured authenticators by their "priority" (highest to lowest). This allows modules to
     * contribute to the decision of which authenticator should be used first. Users can rewrite this in their
     * own configuration if necessary.
     *
     * @param array $authenticators
     */
    public function prioritiseAuthenticators(array &$authenticators): void
    {
        usort($authenticators, function ($a, $b) {
            // Set some default values
            if (!isset($a['priority'])) {
                $a['priority'] = 10;
            }
            if (!isset($b['priority'])) {
                $b['priority'] = 10;
            }

            return $b['priority'] - $a['priority'];
        });
    }
}
