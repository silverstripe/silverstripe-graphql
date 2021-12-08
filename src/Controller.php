<?php

namespace SilverStripe\GraphQL;

use Exception;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use InvalidArgumentException;
use LogicException;
use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\EventDispatcher\Dispatch\Dispatcher;
use SilverStripe\EventDispatcher\Symfony\Event;
use SilverStripe\GraphQL\Auth\Handler;
use SilverStripe\GraphQL\PersistedQuery\RequestProcessor;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface;
use SilverStripe\GraphQL\QueryHandler\QueryStateProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\QueryHandler\RequestContextProvider;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\QueryHandler\TokenContextProvider;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use BadMethodCallException;

/**
 * Top level controller for handling graphql requests.
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
        'Allow-Credentials' => '',
        'Max-Age' => 86400, // 86,400 seconds = 1 day.
    ];

    /**
     * @var string
     */
    private $schemaKey;

    /**
     * @var QueryHandlerInterface
     */
    private $queryHandler;

    /**
     * Override the default cors config per instance
     * @var array
     */
    protected $corsConfig = [];

    /**
     * @var bool
     */
    protected $autobuildSchema = true;

    /**
     * @param string|null $schemaKey
     * @param QueryHandlerInterface|null $queryHandler
     */
    public function __construct(
        ?string $schemaKey = null,
        ?QueryHandlerInterface $queryHandler = null
    ) {
        parent::__construct();
        $this->setSchemaKey($schemaKey);
        $handler = $queryHandler ?: Injector::inst()->create(QueryHandlerInterface::class);
        $this->setQueryHandler($handler);
    }

    /**
     * Handles requests to the index action (e.g. /graphql)
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     * @throws BadMethodCallException
     * @throws HTTPResponse_Exception
     */
    public function index(HTTPRequest $request): HTTPResponse
    {
        if (!$this->schemaKey) {
            throw new BadMethodCallException('Cannot query the controller without a schema key defined');
        }

        $stage = $request->param('Stage');
        if ($stage) {
            Versioned::set_stage($stage);
        }

        // Check for a possible CORS preflight request and handle if necessary
        if ($request->httpMethod() === 'OPTIONS') {
            return $this->handleOptions($request);
        }

        // Grab a list of queries from the request
        $queryList = $this->getQueriesFromRequest($request);
        if (empty($queryList)) {
            $this->httpError(400, 'This endpoint requires a "query" parameter');
        }

        // Process either a single query, or an array (aka batch) of queries
        if (count($queryList) === 1) {
            [$query, $variables] = $queryList[0];
            $result = $this->processQuery($query, $variables);
        } else {
            $result = [];
            foreach ($queryList as $queryData) {
                [$query, $variables] = $queryData;
                $result[] = $this->processQuery($query, $variables);
            }
        }

        $response = $this->addCorsHeaders($request, new HTTPResponse(json_encode($result)));
        return $response->addHeader('Content-Type', 'application/json');
    }

    /**
     * Get a list of queries from the request. JSON requests may contain one or many queries,
     * non-JSON requests contain a single query
     *
     * @param HTTPRequest $request
     * @return array|array[]
     */
    protected function getQueriesFromRequest(HTTPRequest $request): array
    {
        $contentType = $request->getHeader('content-type');
        $isJson = preg_match('#^application/json\b#', $contentType);
        if (!$isJson) {
            /** @var RequestProcessor $persistedProcessor  */
            $persistedProcessor = Injector::inst()->get(RequestProcessor::class);
            [$query, $variables] = $persistedProcessor->getRequestQueryVariables($request);
            // No query found, return an empty result
            if (!$query) {
                return [];
            }

            // Return a single query when data is provided in a non-json format
            return [
                [$query, (array)$variables]
            ];
        }

        $rawBody = $request->getBody();
        $data = json_decode($rawBody ?: '', true);
        // No queries found, so return an empty result
        if (!is_array($data)) {
            return [];
        }

        // An associative array is a request containing a single query
        if (ArrayLib::is_associative($data)) {
            $query = $data['query'] ?? null;
            $variables = $data['variables'] ?? [];
            return [
                [$query, (array)$variables]
            ];
        }

        // An indexed array is a batch of queries, so extract the relevant data from each of them
        $queries = [];
        foreach ($data as $queryData) {
            $query = $queryData['query'] ?? null;
            $variables = $queryData['variables'] ?? [];
            $queries[] = [$query, (array)$variables];
        }

        return $queries;
    }

    /**
     * @param string $query
     * @param array|null $variables
     * @return array
     */
    protected function processQuery(string $query, array $variables = []): array
    {
        try {
            $builder = SchemaBuilder::singleton();
            $graphqlSchema = $builder->getSchema($this->getSchemaKey());
            if (!$graphqlSchema && $this->autobuildEnabled()) {
                // clear the cache on autobuilds until we trust it more. Maybe
                // make this configurable.
                $clear = true;
                $graphqlSchema = $builder->buildByName($this->getSchemaKey(), $clear);
            } elseif (!$graphqlSchema) {
                throw new SchemaBuilderException(sprintf(
                    'Schema %s has not been built.',
                    $this->getSchemaKey()
                ));
            }
            $handler = $this->getQueryHandler();
            $this->applyContext($handler);
            $queryDocument = Parser::parse(new Source($query));
            $ctx = $handler->getContext();
            $result = $handler->query($graphqlSchema, $query, $variables);

            // Fire an event
            $eventContext = [
                'schema' => $graphqlSchema,
                'schemaKey' => $this->getSchemaKey(),
                'query' => $query,
                'context' => $ctx,
                'variables' => $variables,
                'result' => $result,
            ];
            $event = QueryHandler::isMutation($query) ? 'graphqlMutation' : 'graphqlQuery';
            $operationName = QueryHandler::getOperationName($queryDocument);
            Dispatcher::singleton()->trigger($event, Event::create($operationName, $eventContext));
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

        return $result;
    }

    /**
     * @return bool
     */
    public function autobuildEnabled(): bool
    {
        return $this->autobuildSchema;
    }

    /**
     * @param bool $autobuildSchema
     * @return Controller
     */
    public function setAutobuildSchema(bool $autobuildSchema): Controller
    {
        $this->autobuildSchema = $autobuildSchema;
        return $this;
    }


    /**
     * Get an instance of the authorization Handler to manage any authentication requirements
     *
     * @return Handler
     */
    public function getAuthHandler(): Handler
    {
        return new Handler;
    }

    /**
     * @return string|null
     */
    public function getToken(): ?string
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
    public function addCorsHeaders(HTTPRequest $request, HTTPResponse $response): HTTPResponse
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
     * @param string|null $origin Origin string
     * @param array $allowedOrigins List of allowed origins
     * @return bool
     */
    protected function validateOrigin(?string $origin, array $allowedOrigins)
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
     * @param QueryHandlerInterface $handler
     * @throws Exception
     */
    protected function applyContext(QueryHandlerInterface $handler)
    {
        $request = $this->getRequest();
        $user = $this->getRequestUser($request);
        $token = $this->getToken();

        $handler->addContextProvider(UserContextProvider::create($user))
                ->addContextProvider(TokenContextProvider::create($token ?: ''))
                ->addContextProvider(RequestContextProvider::create($request));
        $schemaContext = SchemaBuilder::singleton()->getConfig($this->getSchemaKey());
        if ($schemaContext) {
            $handler->addContextProvider(SchemaConfigProvider::create($schemaContext));
        }
        $handler->addContextProvider(QueryStateProvider::create());
    }

    /**
     * Get (or infer) value of Origin header
     *
     * @param HTTPRequest $request
     * @return string|null
     */
    protected function getRequestOrigin(HTTPRequest $request): ?string
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
     * Get user and validate for this request
     *
     * @param HTTPRequest $request
     * @return Member|null
     * @throws Exception
     */
    protected function getRequestUser(HTTPRequest $request): ?Member
    {
        // Check authentication
        $member = $this->getAuthHandler()->requireAuthentication($request) ?: null;

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
     * @param string $schemaKey
     * @return $this
     */
    public function setSchemaKey(string $schemaKey): self
    {
        $this->schemaKey = $schemaKey;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSchemaKey(): ?string
    {
        return $this->schemaKey;
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
    public function setQueryHandler(QueryHandlerInterface $queryHandler): self
    {
        $this->queryHandler = $queryHandler;
        return $this;
    }
}
