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
     */
    private static array $cors = [
        'Enabled' => false, // Off by default
        'Allow-Origin' => [], // List of all allowed origins; Deny by default
        'Allow-Headers' => 'Authorization, Content-Type',
        'Allow-Methods' => 'GET, POST, OPTIONS',
        'Allow-Credentials' => '',
        'Max-Age' => 86400, // 86,400 seconds = 1 day.
    ];

    private string $schemaKey;

    private QueryHandlerInterface $queryHandler;

    /**
     * Override the default cors config per instance
     */
    protected array $corsConfig = [];

    protected bool $autobuildSchema = true;

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
     * @throws InvalidArgumentException
     */
    public function index(HTTPRequest $request): HTTPResponse
    {
        if (!$this->schemaKey) {
            throw new BadMethodCallException('Cannot query the controller without a schema key defined');
        }

        if (class_exists(Versioned::class) && $stage = $request->param('Stage')) {
            Versioned::set_stage($stage);
        }

        // Check for a possible CORS preflight request and handle if necessary
        if ($request->httpMethod() === 'OPTIONS') {
            return $this->handleOptions($request);
        }

        // Main query handling
        try {
            list($query, $variables) = $this->getRequestQueryVariables($request);
            if (!$query) {
                $this->httpError(400, 'This endpoint requires a "query" parameter');
            }
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

            // Fire an eventYou
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

        $response = $this->addCorsHeaders($request, new HTTPResponse(json_encode($result)));
        return $response->addHeader('Content-Type', 'application/json');
    }

    public function autobuildEnabled(): bool
    {
        return $this->autobuildSchema;
    }

    public function setAutobuildSchema(bool $autobuildSchema): Controller
    {
        $this->autobuildSchema = $autobuildSchema;
        return $this;
    }

    /**
     * Get an instance of the authorization Handler to manage any authentication requirements
     */
    public function getAuthHandler(): Handler
    {
        return new Handler;
    }

    public function getToken(): ?string
    {
        return $this->getRequest()->getHeader('X-CSRF-TOKEN');
    }

    /**
     * Process the CORS config options and add the appropriate headers to the response.
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

    public function getCorsConfig(): array
    {
        return $this->corsConfig;
    }

    public function getMergedCorsConfig(): array
    {
        $defaults = Config::inst()->get(static::class, 'cors');
        $override = $this->corsConfig;

        return array_merge($defaults, $override);
    }

    public function setCorsConfig(array $config): self
    {
        $this->corsConfig = array_merge($this->corsConfig, $config);

        return $this;
    }

    /**
     * Validate an origin matches a set of allowed origins
     */
    protected function validateOrigin(?string $origin, array $allowedOrigins): bool
    {
        if (empty($allowedOrigins) || empty($origin)) {
            return false;
        }
        foreach ($allowedOrigins as $allowedOrigin) {
            if ($allowedOrigin === '*') {
                return true;
            }
            if (strcasecmp($allowedOrigin ?? '', $origin ?? '') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @throws Exception
     */
    protected function applyContext(QueryHandlerInterface $handler): void
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
            $refererParts = parse_url($referer ?? '');
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
     */
    protected function handleOptions(HTTPRequest $request): HTTPResponse
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
     * @throws LogicException
     */
    protected function getRequestQueryVariables(HTTPRequest $request): array
    {
        $contentType = $request->getHeader('content-type');
        $isJson = preg_match('#^application/json\b#', $contentType ?? '');
        if ($isJson) {
            $rawBody = $request->getBody();
            $data = json_decode($rawBody ?: '', true);
            $query = isset($data['query']) ? $data['query'] : null;
            $variables = isset($data['variables']) ? (array)$data['variables'] : null;
        } else {
            /** @var RequestProcessor $persistedProcessor  */
            $persistedProcessor = Injector::inst()->get(RequestProcessor::class);
            list($query, $variables) = $persistedProcessor->getRequestQueryVariables($request);
        }

        return [$query, $variables];
    }

    /**
     * Get user and validate for this request
     *
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

    public function setSchemaKey(string $schemaKey): self
    {
        $this->schemaKey = $schemaKey;
        return $this;
    }

    public function getSchemaKey(): ?string
    {
        return $this->schemaKey;
    }

    public function getQueryHandler(): QueryHandlerInterface
    {
        return $this->queryHandler;
    }

    public function setQueryHandler(QueryHandlerInterface $queryHandler): self
    {
        $this->queryHandler = $queryHandler;
        return $this;
    }
}
