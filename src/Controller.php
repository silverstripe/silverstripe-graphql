<?php

namespace SilverStripe\GraphQL;

use Exception;
use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\Auth\Handler;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;

/**
 * Top level controller for handling graphql requests.
 * @todo CSRF protection (or token-based auth)
 * @skipUpgrade
 */
class Controller extends BaseController
{
    /**
     * Cors default config
     *
     * @config
     * @var array
     */
    private static $cors = [
        'Enabled' => false, // Off by default
        'Allow-Origin' => [], // List of all allowed origins; Deny by default
        'Allow-Headers' => 'Authorization, Content-Type',
        'Allow-Methods' => 'GET, POST, OPTIONS',
        'Allow-Credentials' => '', // Off by default, set to 'true' to allow session cookies from other origins
        'Max-Age' => 86400, // 86,400 seconds = 1 day.
    ];

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
        if ($request->httpMethod() === 'OPTIONS') {
            return $this->handleOptions($request);
        }

        // Main query handling
        try {
            $manager = $this->getManager();

            // Check and validate user for this request
            $member = $this->getRequestUser($request);
            if ($member) {
                $manager->setMember($member);
            }

            // Parse input
            list($query, $variables) = $this->getRequestQueryVariables($request);

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
        $config = Config::inst()->get(static::class, 'schema');
        $manager = Manager::createFromConfig($config);
        $this->setManager($manager);
        return $manager;
    }

    /**
     * @param Manager $manager
     * @return $this
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
        return $this;
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
        $corsConfig = Config::inst()->get(static::class, 'cors');

        // If CORS is disabled don't add the extra headers. Simply return the response untouched.
        if (empty($corsConfig['Enabled'])) {
            return $response;
        }

        // Calculate origin
        $origin = $this->getRequestOrigin($request);

        // Check if valid
        $allowedOrigins = (array)$corsConfig['Allow-Origin'];
        $originAuthorised = $this->validateOrigin($origin, $allowedOrigins);
        if (!$originAuthorised) {
            $this->httpError(403, "Access Forbidden");
        }

        $response->addHeader('Access-Control-Allow-Origin', $origin);
        $response->addHeader('Access-Control-Allow-Headers', $corsConfig['Allow-Headers']);
        $response->addHeader('Access-Control-Allow-Methods', $corsConfig['Allow-Methods']);
        $response->addHeader('Access-Control-Allow-Credentials', $corsConfig['Allow-Credentials']);
        $response->addHeader('Access-Control-Max-Age', $corsConfig['Max-Age']);

        return $response;
    }

    /**
     * Validate an origin matches a set of allowed origins
     *
     * @param string $origin Origin string
     * @param array $allowedOrigins List of allowed origins
     * @return bool
     */
    protected function validateOrigin($origin, $allowedOrigins)
    {
        if (empty($allowedOrigins) || empty($origin)) {
            return false;
        }
        foreach ($allowedOrigins as $allowedOrigin) {
            if ($allowedOrigin === '*') {
                return true;
            }
            if (strcasecmp($allowedOrigin, $origin) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get (or infer) value of Origin header
     *
     * @param HTTPRequest $request
     * @return string|null
     */
    protected function getRequestOrigin(HTTPRequest $request)
    {
        // Prefer Origin header
        $origin = $request->getHeader('Origin');
        if ($origin) {
            return $origin;
        }

        // Check referer
        $referer = $request->getHeader('Referer');
        if ($referer) {
            // Extract protocol, hostname, and port
            $refererParts = parse_url($referer);
            if (!$refererParts) {
                return null;
            }
            // Rebuild
            $origin = $refererParts['scheme'] . '://' . $refererParts['host'];
            if (isset($refererParts['port'])) {
                $origin .= ':' . $refererParts['port'];
            }
            return $origin;
        }

        return null;
    }

    /**
     * Response for HTTP OPTIONS request
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    protected function handleOptions(HTTPRequest $request)
    {
        $response = HTTPResponse::create();
        $corsConfig = Config::inst()->get(self::class, 'cors');
        if ($corsConfig['Enabled']) {
            // CORS config is enabled and the request is an OPTIONS pre-flight.
            // Process the CORS config and add appropriate headers.
            $this->addCorsHeaders($request, $response);
        } else {
            // CORS is disabled but we have received an OPTIONS request.  This is not a valid request method in this
            // situation.  Return a 405 Method Not Allowed response.
            $this->httpError(405, "Method Not Allowed");
        }
        return $response;
    }

    /**
     * Parse query and variables from the given request
     *
     * @param HTTPRequest $request
     * @return array Array containing query and variables as a pair
     */
    protected function getRequestQueryVariables(HTTPRequest $request)
    {
        $contentType = $request->getHeader('content-type');
        $isJson = preg_match('#^application/json\b#', $contentType);
        if ($isJson) {
            $rawBody = $request->getBody();
            $data = json_decode($rawBody ?: '', true);
            $query = isset($data['query']) ? $data['query'] : null;
            $variables = isset($data['variables']) ? (array)$data['variables'] : null;
        } else {
            $query = $request->requestVar('query');
            $variables = json_decode($request->requestVar('variables'), true);
        }
        return [$query, $variables];
    }

    /**
     * Get user and validate for this request
     *
     * @param HTTPRequest $request
     * @return Member
     */
    protected function getRequestUser(HTTPRequest $request)
    {
        // Check authentication
        $member = $this->getAuthHandler()->requireAuthentication($request);

        // Check authorisation
        $permissions = $request->param('Permissions');
        if (!$permissions) {
            return $member;
        }

        // If permissions requested require authentication
        if (!$member) {
            throw new Exception("Authentication required");
        }

        // Check authorisation for this member
        $allowed = Permission::checkMember($member, $permissions);
        if (!$allowed) {
            throw new Exception("Not authorised");
        }
        return $member;
    }
}
