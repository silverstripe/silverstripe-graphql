<?php

namespace SilverStripe\GraphQL;

use Exception;
use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Auth\Handler;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Versioned\Versioned;

/**
 * Top level controller for handling graphql requests.
 * @todo CSRF protection (or token-based auth)
 * @skipUpgrade
 */
class Controller extends BaseController implements Flushable
{
    const CACHE_FILENAME = 'types.graphql';

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
        'Max-Age' => 86400, // 86,400 seconds = 1 day.
    ];

    /**
     * If true, store the fragment JSON in a flat file in assets/
     * @var bool
     * @config
     */
    private static $cache_types_in_filesystem = false;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var GeneratedAssetHandler
     */
    protected $assetHandler;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager = null)
    {
        parent::__construct();
        $this->manager = $manager;

        if ($this->manager && $this->manager->getSchemaKey()) {
            // Side effect. This isn't ideal, but having multiple instances of StaticSchema
            // is a massive architectural change.
            StaticSchema::reset();

            $this->manager->configure();
        }
    }

    /**
     * Handles requests to the index action (e.g. /graphql)
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
            $manager = $this->getManager($request);
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
     * @param HTTPRequest $request
     * @return Manager
     */
    public function getManager($request = null)
    {
        $manager = null;
        if (!$request) {
            $request = $this->getRequest();
        }
        if ($this->manager) {
            $manager = $this->manager;
        } else {
            // Get a service rather than an instance (to allow procedural configuration)
            $config = Config::inst()->get(static::class, 'schema');
            $manager = Manager::createFromConfig($config);
        }
        $this->applyManagerContext($manager, $request);
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
     * @param GeneratedAssetHandler $handler
     * @return $this
     */
    public function setAssetHandler(GeneratedAssetHandler $handler)
    {
        $this->assetHandler = $handler;

        return $this;
    }

    /**
     * @return GeneratedAssetHandler
     */
    public function getAssetHandler()
    {
        return $this->assetHandler;
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
     * @return string
     */
    public function getToken()
    {
        return $this->getRequest()->getHeader('X-CSRF-TOKEN');
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
     * @param Manager $manager
     * @param HTTPRequest $request
     * @throws Exception
     */
    protected function applyManagerContext(Manager $manager, HTTPRequest $request)
    {
        // Add request context to Manager
        $manager->addContext('token', $this->getToken());
        $method = null;
        if ($request->isGET()) {
            $method = 'GET';
        } elseif ($request->isPOST()) {
            $method = 'POST';
        }
        $manager->addContext('httpMethod', $method);

        // Check and validate user for this request
        $member = $this->getRequestUser($request);
        if ($member) {
            $manager->setMember($member);
        }
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

    /**
     * Introspect the schema and persist it to the filesystem
     * @throws Exception
     */
    public function writeSchemaToFilesystem()
    {
        $manager = $this->getManager();
        try {
            $types = StaticSchema::inst()->introspectTypes($manager);
        } catch (Exception $e) {
            throw new Exception(sprintf(
                'There was an error caching the GraphQL types: %s',
                $e->getMessage()
            ));
        }

        $this->writeTypes(json_encode($types));
    }

    public function removeSchemaFromFilesystem()
    {
        if (!$this->getAssetHandler()) {
            return;
        }

        $this->getAssetHandler()->removeContent($this->generateCacheFilename());
    }

    /**
     * @param string $content
     */
    public function writeTypes($content)
    {
        if (!$this->getAssetHandler()) {
            return;
        }
        $this->getAssetHandler()->setContent($this->generateCacheFilename(), $content);
    }

    /**
     * Write the types json to a flat file, if silverstripe/assets is available
     */
    public function processTypeCaching()
    {
        if ($this->config()->cache_types_in_filesystem) {
            $this->writeSchemaToFilesystem();
        } else {
            $this->removeSchemaFromFilesystem();
        }
    }

    public static function flush()
    {
        // This is a bit of a hack to find all registered GraphQL servers. Depends on them
        // being routed through Director.
        $routes = Director::config()->get('rules');
        foreach ($routes as $pattern => $controllerInfo) {
            $routeClass = (is_string($controllerInfo)) ? $controllerInfo : $controllerInfo['Controller'];
            if (stristr($routeClass, Controller::class) !== false) {
                try {
                    $inst = Injector::inst()->convertServiceProperty($routeClass);
                    if ($inst instanceof Controller) {
                        /* @var Controller $inst */
                        $inst->processTypeCaching();
                    }
                } catch (DatabaseException $e) {
                    // Allow failures on table doesn't exist or no database selected as we're flushing in first DB build
                    $messageByLine = explode(PHP_EOL, $e->getMessage());

                    // Get the last line
                    $last = array_pop($messageByLine);

                    if (strpos($last, 'No database selected') === false
                        && !preg_match('/\s*(table|relation) .* does(n\'t| not) exist/i', $last)
                    ) {
                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    protected function generateCacheFilename()
    {
        return $this->getManager()->getSchemaKey() . '.' . self::CACHE_FILENAME;
    }
}
