<?php

namespace SilverStripe\GraphQL\Auth;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;

/**
 * An AuthenticatorInterface is responsible for authenticating against a SilverStripe CMS Member from
 * the given request data.
 *
 * It should return the authenticated Member if successful so that GraphQL can
 * use it in place of the Member from the session for permission checks such as DataObject::canView.
 */
interface AuthenticatorInterface
{
    /**
     * Given the current request, authenticate the request for non-session authorization (outside the CMS).
     *
     * The Member returned from this method will be provided to the Manager for use in the OperationResolver context
     * in place of the current CMS member.
     *
     * Authenticators can be given a priority. In this case, the authenticator with the highest priority will be
     * returned first. If not provided, it will default to a low number.
     *
     * An example for configuring the BasicAuthAuthenticator:
     *
     * <code>
     * SilverStripe\GraphQL:
     *   authenticators:
     *     - class: SilverStripe\GraphQL\Auth\BasicAuthAuthenticator
     *       priority: 10
     * </code>
     *
     * @param  HTTPRequest $request The current HTTP request
     * @return Member               If authentication is successful
     * @throws ValidationException  If authentication fails
     */
    public function authenticate(HTTPRequest $request);

    /**
     * Determine if this authenticator is applicable to the current request
     *
     * @param HTTPRequest $request
     * @return bool
     */
    public function isApplicable(HTTPRequest $request);
}
