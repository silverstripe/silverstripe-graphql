<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\GraphQL\Auth\Handler;
use SilverStripe\ORM\Versioning\Versioned;
use Exception;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Top level controller for handling graphql requests.
 * @todo CSRF protection (or token-based auth)
 */
class Controller extends BaseController
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * Handles requests to /graphql (index action)
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function index(HTTPRequest $request)
    {
        $stage = $request->param('Stage');
        if ($stage && in_array($stage, [Versioned::DRAFT, Versioned::LIVE])) {
            Versioned::set_stage($stage);
        }

        // Check for a possible CORS preflight request and handle if necessary
        // Refer issue 66:  https://github.com/silverstripe/silverstripe-graphql/issues/66
        $corsConfig = Config::inst()->get('SilverStripe\GraphQL', 'cors');
        $corsEnabled = true; // Default to have CORS turned on.

        if ($corsConfig && isset($corsConfig['Enabled']) && !$corsConfig['Enabled']) {
            // Dev has turned off CORS
            $corsEnabled = false;
        }
        if ($corsEnabled && $request->httpMethod() == 'OPTIONS') {
            // CORS config is enabled and the request is an OPTIONS pre-flight.
            // Process the CORS config and add appropriate headers.
            $response = new HTTPResponse();
            return $this->addCorsHeaders($request, $response);
        } elseif (!$corsEnabled && $request->httpMethod() == 'OPTIONS') {
            // CORS is disabled but we have received an OPTIONS request.  This is not a valid request method in this
            // situation.  Return a 405 Method Not Allowed response.
            return $this->httpError(405, "Method Not Allowed");
        }

        $contentType = $request->getHeader('Content-Type') ?: $request->getHeader('content-type');
        $isJson = preg_match('#^application/json\b#', $contentType);
        if ($isJson) {
            $rawBody = $request->getBody();
            $data = json_decode($rawBody ?: '', true);
            $query = isset($data['query']) ? $data['query'] : null;
            $variables = isset($data['variables']) ? $data['variables'] : null;
        } else {
            $query = $request->requestVar('query');
            $variables = json_decode($request->requestVar('variables'), true);
        }

        $this->setManager($manager = $this->getManager());

        try {
            // Check authentication
            $member = $this->getAuthHandler()->requireAuthentication($request);
            if ($member) {
                $manager->setMember($member);
            }

            // Check authorisation
            $permissions = $request->param('Permissions');
            if ($permissions) {
                if (!$member) {
                    throw new \Exception("Authentication required");
                }
                $allowed = Permission::checkMember($member, $permissions);
                if (!$allowed) {
                    throw new \Exception("Not authorised");
                }
            }

            // Run query
            $result = $manager->query($query, $variables);
        } catch (Exception $exception) {
            $error = ['message' => $exception->getMessage()];

            if (Director::isDev()) {
                $error['code'] = $exception->getCode();
                $error['file'] = $exception->getFile();
                $error['line'] = $exception->getLine();
                $error['trace'] = $exception->getTrace();
            }

            $result = [
                'errors' => [$error]
            ];
        }

        $response = $this->addCorsHeaders($request, new HTTPResponse(json_encode($result)));
        return $response->addHeader('Content-Type', 'application/json');
    }

    /**
     * @return Manager
     */
    public function getManager()
    {
        if ($this->manager) {
            return $this->manager;
        }

        // Get a service rather than an instance (to allow procedural configuration)
        $config = Config::inst()->get('SilverStripe\GraphQL', 'schema');
        $manager = Manager::createFromConfig($config);

        return $manager;
    }

    /**
     * @param Manager $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * Get an instance of the authorization Handler to manage any authentication requirements
     *
     * @return Handler
     */
    public function getAuthHandler()
    {
        return new Handler;
    }

    /**
     * Process the CORS config options and add the appropriate headers to the response.
     *
     * @param HTTPRequest $request
     * @param HTTPResponse $response
     * @return HTTPResponse
     */
    public function addCorsHeaders(HTTPRequest $request, HTTPResponse $response)
    {
        $corsConfig = Config::inst()->get('SilverStripe\GraphQL', 'cors');
        if (empty($corsConfig['Enabled'])) {
            // If CORS is disabled don't add the extra headers. Simply return the response untouched.
            return $response;
        }

        // Allow Origins header.
        if (is_string($corsConfig['Allow-Origin'])) {
            $allowedOrigins = [$corsConfig['Allow-Origin']];
        } else {
            $allowedOrigins = $corsConfig['Allow-Origin'];
        }
        if (!empty($allowedOrigins)) {
            $origin = $request->getHeader('Origin');
            if ($origin) {
                $originAuthorised = false;
                foreach ($allowedOrigins as $allowedOrigin) {
                    if ($allowedOrigin == $origin) {
                        $response->addHeader("Access-Control-Allow-Origin", $origin);
                        $originAuthorised = true;
                        break;
                    }
                }

                if (!$originAuthorised) {
                    return $this->httpError(403, "Access Forbidden");
                }
            }
        } else {
            return $this->httpError(403, "Access Forbidden");
        }

        $response->addHeader('Access-Control-Allow-Headers', $corsConfig['Allow-Headers']);
        $response->addHeader('Access-Control-Allow-Methods', $corsConfig['Allow-Methods']);
        $response->addHeader('Access-Control-Max-Age', $corsConfig['Max-Age']);

        return $response;
    }
}
