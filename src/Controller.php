<?php

namespace SilverStripe\GraphQL;

use Exception;
use LogicException;
use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\NullHTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Auth\Handler;
use SilverStripe\GraphQL\Dev\Benchmark;
use SilverStripe\GraphQL\Dev\State\DisableTypeCacheState;
use SilverStripe\GraphQL\Permission\MemberContextProvider;
use SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Schema\Interfaces\ContextProvider;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use InvalidArgumentException;

/**
 * Top level controller for handling graphql requests.
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
        'Allow-Credentials' => '',
        'Max-Age' => 86400, // 86,400 seconds = 1 day.
    ];

    /**
     * If true, store the fragment JSON in a flat file in assets/
     * @var bool
     * @config
     */
    private static $cache_types_in_filesystem = false;

    /**
     * Toggles caching types to the file system on flush
     * This is set to false in test state @see DisableTypeCacheState
     *
     * @var bool
     * @config
     */
    private static $cache_on_flush = true;

    /**
     * @var Schema
     */
    private $builder;

    /**
     * @var QueryHandlerInterface
     */
    private $queryHandler;

    /**
     * @var GeneratedAssetHandler
     */
    protected $assetHandler;

    /**
     * Override the default cors config per instance
     * @var array
     */
    protected $corsConfig = [];

    /**
     * @param Schema $builder
     * @param QueryHandlerInterface $queryHandler
     */
    public function __construct(Schema $builder, QueryHandlerInterface $queryHandler)
    {
        parent::__construct();
        $this->setBuilder($builder);
        $this->setQueryHandler($queryHandler);
    }

    /**
     * Handles requests to the index action (e.g. /graphql)
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     * @throws InvalidArgumentException
     */
    public function index(HTTPRequest $request)
    {
        $stage = $request->param('Stage');
        if ($stage) {
            Versioned::set_stage($stage);
        }
        // Check for a possible CORS preflight request and handle if necessary
        // Refer issue 66:  https://github.com/silverstripe/silverstripe-graphql/issues/66
        if ($request->httpMethod() === 'OPTIONS') {
            return $this->handleOptions($request);
        }
        $queryPerf = '';
        $schemaPerf = '';
        // Main query handling
        try {
            list($query, $variables) = $this->getRequestQueryVariables($request);
            if (!$query) {
                $this->httpError(400, 'This endpoint requires a "query" parameter');
            }

            // Temporary, maybe useful by feature flag later..
            Benchmark::start('schema-perf');
            $schema = $this->getBuilder()->getSchema();
            $schemaPerf = Benchmark::end('schema-perf', '%sms', true);
            $handler = $this->getQueryHandler();
            if ($handler instanceof ContextProvider) {
                $this->applyContext($handler, $request);
            }
            Benchmark::start('query-perf');
            $result = $handler->query($schema, $query, $variables);
            $queryPerf = Benchmark::end('query-perf', '%sms', true);

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
        return $response->addHeader('Content-Type', 'application/json')
            ->addHeader('X-QueryPerf', $queryPerf)
            ->addHeader('X-SchemaPerf', $schemaPerf);
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
        $corsConfig = $this->getMergedCorsConfig();

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

        if (isset($corsConfig['Allow-Credentials'])) {
            $response->addHeader('Access-Control-Allow-Credentials', $corsConfig['Allow-Credentials']);
        }

        return $response;
    }

    /**
     * @return array
     */
    public function getCorsConfig(): array
    {
        return $this->corsConfig;
    }

    /**
     * @return array
     */
    public function getMergedCorsConfig(): array
    {
        $defaults = Config::inst()->get(static::class, 'cors');
        $override = $this->corsConfig;

        return array_merge($defaults, $override);
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setCorsConfig(array $config): self
    {
        $this->corsConfig = array_merge($this->corsConfig, $config);

        return $this;
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
     * @param ContextProvider $provider
     * @param HTTPRequest $request
     * @throws Exception
     */
    protected function applyContext(ContextProvider $provider, HTTPRequest $request)
    {
        $provider->addContext('token', $this->getToken());
        $method = null;
        if ($request->isGET()) {
            $method = 'GET';
        } elseif ($request->isPOST()) {
            $method = 'POST';
        }
        $provider->addContext('httpMethod', $method);

        if ($provider instanceof MemberContextProvider) {
            // Check and validate user for this request
            /* @var MemberContextProvider $provider */
            $member = $this->getRequestUser($request);
            if ($member) {
                $provider->setMemberContext($member);
            }
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
     * @throws LogicException
     */
    protected function getRequestQueryVariables(HTTPRequest $request)
    {
        $contentType = $request->getHeader('content-type');
        $isJson = preg_match('#^application/json\b#', $contentType);
        if ($isJson) {
            $rawBody = $request->getBody();
            $data = json_decode($rawBody ?: '', true);
            $query = isset($data['query']) ? $data['query'] : null;
            $id = isset($data['id']) ? $data['id'] : null;
            $variables = isset($data['variables']) ? (array)$data['variables'] : null;
        } else {
            $query = $request->requestVar('query');
            $id = $request->requestVar('id');
            $variables = json_decode($request->requestVar('variables'), true);
        }

        if ($id) {
            if ($query) {
                throw new LogicException('Cannot pass a query when an ID has been specified.');
            }
            $query = $this->manager->getQueryFromPersistedID($id);
        }

        return [$query, $variables];
    }

    /**
     * Get user and validate for this request
     *
     * @param HTTPRequest $request
     * @return Member
     * @throws Exception
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
        try {
            $types = $this->introspectTypes();
        } catch (Exception $e) {
            throw new Exception(sprintf(
                'There was an error caching the GraphQL types: %s',
                $e->getMessage()
            ));
        }

        $this->writeTypes(json_encode($types));
    }

    /**
     * @return array
     * @throws Exception
     */
    public function introspectTypes()
    {
        $fragments = $this->getQueryHandler()->query(
            $this->getBuilder()->getSchema(),
            <<<GRAPHQL
query IntrospectionQuery {
    __schema {
      types {
        kind
        name
        possibleTypes {
          name
        }
      }
    }
}
GRAPHQL
        );

        if (isset($fragments['errors'])) {
            $messages = array_map(function ($error) {
                return $error['message'];
            }, $fragments['errors']);

            throw new Exception(sprintf(
                'There were some errors with the introspection query: %s',
                implode(PHP_EOL, $messages)
            ));
        }

        return $fragments;
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

    /**
     * @return Schema
     */
    public function getBuilder(): Schema
    {
        return $this->builder;
    }

    /**
     * @param Schema $builder
     * @return Controller
     */
    public function setBuilder(Schema $builder): Controller
    {
        $this->builder = $builder;
        return $this;
    }

    /**
     * @return QueryHandlerInterface
     */
    public function getQueryHandler(): QueryHandlerInterface
    {
        return $this->queryHandler;
    }

    /**
     * @param QueryHandlerInterface $queryHandler
     * @return Controller
     */
    public function setQueryHandler(QueryHandlerInterface $queryHandler): Controller
    {
        $this->queryHandler = $queryHandler;
        return $this;
    }



    public static function flush()
    {
        if (!self::config()->get('cache_on_flush')) {
            return;
        }

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
        return $this->getBuilder()->getSchemaKey() . '.' . self::CACHE_FILENAME;
    }
}
