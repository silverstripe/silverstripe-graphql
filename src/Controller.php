<?php

namespace SilverStripe\GraphQL;

use BadMethodCallException;
use Exception;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams;
use GraphQL\Server\RequestError;
use GraphQL\Type\Schema;
use GraphQL\Utils\Utils;
use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Auth\Handler;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface;
use SilverStripe\GraphQL\QueryHandler\QueryStateProvider;
use SilverStripe\GraphQL\QueryHandler\RequestContextProvider;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\QueryHandler\TokenContextProvider;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;

/**
 * Top level controller for handling graphql requests.
 * @skipUpgrade
 */
class Controller extends BaseController
{
    private static $url_handlers = [
        'OPTIONS /' => 'handleOptions'
    ];

    private static $allowed_actions = [
        'handleOptions'
    ];

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
     * @param HTTPRequest $request
     * @return HTTPResponse
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

        try {
            $operations = $this->parseRequest($request);
            $schema = $this->getSchema();

            /** @var QueryHandler $handler */
            $handler = $this->getQueryHandler();
            $this->applyContext($handler);

            $result = $handler->executeOperations($operations, $schema);
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
     * @return OperationParams|OperationParams[]
     * @throws HTTPResponse_Exception
     * @throws RequestError
     */
    protected function parseRequest(HTTPRequest $request)
    {
        $helper = new Helper();
        $method = $request->httpMethod();
        $queryParams = $request->getVars();

        if ($method !== 'POST') {
            return $helper->parseRequestParams($method, [], $queryParams);
        }

        $body = [];
        $contentType = $request->getHeader('content-type');
        if (stripos($contentType, 'application/json') !== false) {
            $body = json_decode($request->getBody() ?: '', true);
            if (!is_array($body)) {
                $this->httpError(400, 'Expected JSON object or array, but got ' . Utils::printSafeJson($body));
            }
        } elseif (stripos($contentType, 'application/graphql') !== false) {
            $body = ['query' => $request->getBody() ?: ''];
        } elseif (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
            $body = $request->postVars();
        } elseif (stripos($contentType, 'multipart/form-data') !== false) {
            $body = $this->inlineFiles($request);
        } else {
            $this->httpError(400, 'Unexpected content type: ' . Utils::printSafeJson($contentType));
        }

        return $helper->parseRequestParams($method, $body, $queryParams);
    }

    /**
     * @param HTTPRequest $request
     * @return array
     * @throws HTTPResponse_Exception
     */
    protected function inlineFiles(HTTPRequest $request): array
    {
        /** @var string|null $mapParam */
        $mapParam = $request->postVar('map');
        if ($mapParam === null) {
            $this->httpError(400, 'Could not find a valid map, be sure to conform to GraphQL multipart request
                specification: https://github.com/jaydenseric/graphql-multipart-request-spec');
        }

        /** @var string|null $operationsParam */
        $operationsParam = $request->postVar('operations');
        if ($operationsParam === null) {
            $this->httpError(400, 'Could not find valid operations, be sure to conform to GraphQL multipart request
                specification: https://github.com/jaydenseric/graphql-multipart-request-spec');
        }

        $operations = json_decode($operationsParam, true) ?: [];
        $map = json_decode($mapParam, true) ?: [];

        foreach ($map as $fileKey => $operationsPaths) {
            $file = $_FILES[(string)$fileKey] ?? [];

            foreach ($operationsPaths as $operationsPath) {
                $this->mergeFileIntoOperation($operations, $operationsPath, $file);
            }
        }

        return $operations;
    }

    /**
     * This method copies $file into $array using dot notation as a path to navigate to nested array keys,
     * e.g. "1.variables.files.0" is equivalent to $array[1]['variables']['files'][0]
     *
     * @param array $array
     * @param string $key
     * @param array $file
     * @return array
     */
    protected function mergeFileIntoOperation(array &$array, string $key, array $file): array
    {
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $file;
        return $array;
    }

    /**
     * @return Schema
     * @throws SchemaBuilderException
     * @throws EmptySchemaException
     * @throws SchemaNotFoundException
     */
    protected function getSchema(): Schema
    {
        $builder = SchemaBuilder::singleton();
        $graphqlSchema = $builder->getSchema($this->getSchemaKey());
        if (!$graphqlSchema && $this->autobuildEnabled()) {
            // Clear the cache on auto-builds until we trust it more. Maybe make this configurable.
            $clear = true;
            $graphqlSchema = $builder->buildByName($this->getSchemaKey(), $clear);
        } elseif (!$graphqlSchema) {
            throw new SchemaBuilderException(sprintf(
                'Schema %s has not been built.',
                $this->getSchemaKey()
            ));
        }

        return $graphqlSchema;
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
     * @throws HTTPResponse_Exception
     */
    public function handleOptions(HTTPRequest $request): HTTPResponse
    {
        $response = HTTPResponse::create();
        $corsConfig = Config::inst()->get(self::class, 'cors');
        if (!$corsConfig['Enabled']) {
            // CORS is disabled, but we have received an OPTIONS request. This is not a valid request method in this
            // situation. Return a 405 Method Not Allowed response.
            $this->httpError(405, "Method Not Allowed");
        }

        // CORS config is enabled and the request is an OPTIONS pre-flight.
        // Process the CORS config and add appropriate headers.
        $this->addCorsHeaders($request, $response);
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
