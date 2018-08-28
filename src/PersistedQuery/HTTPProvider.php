<?php

namespace SilverStripe\GraphQL\PersistedQuery;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use InvalidArgumentException;
use Exception;
use SilverStripe\Core\Injector\Injector;

/**
 * Class HTTPProvider
 * @package SilverStripe\GraphQL\PersistedQuery
 */
class HTTPProvider implements PersistedQueryMappingProvider
{
    use Configurable, Injectable;

    /**
     * Timeout for the HTTP request
     * @var int
     * @config
     */
    private static $timeout = 5;

    /**
     * Example:
     * <code>
     * SilverStripe\Core\Injector\Injector:
     *   SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
     *     class :SilverStripe\GraphQL\PersistedQuery\HTTPProvider:
     *       properties:
     *         schemaMapping:
     *           default: 'http://example.com/mapping.json'
     * </code>
     *
     * Note: The mapping supports multi-schema feature, you can have other schemaKey rather than 'default'
     *
     * @var array
     * @config
     */
    protected $schemaToURL = [
        'default' => ''
    ];

    /**
     * @var HTTPClient
     */
    protected $client;

    /**
     * A cache of schema key to HTTP responses
     * @var array
     */
    protected $responseCache = [];

    /**
     * HTTPProvider constructor.
     * @param HTTPClient $client
     */
    public function __construct(HTTPClient $client = null)
    {
        if (!$client) {
            $client = Injector::inst()->get(GuzzleHTTPClient::class);
        }
        $this->setClient($client);
    }

    /**
     * return a map from <id> to <query>
     *
     * @param string $schemaKey
     * @return array
     */
    public function getQueryMapping($schemaKey = 'default')
    {
        if (isset($this->responseCache[$schemaKey])) {
            return $this->responseCache[$schemaKey];
        }

        /** @noinspection PhpUndefinedFieldInspection */
        /** @noinspection StaticInvocationViaThisInspection */
        $urlWithKey = $this->getSchemaMapping();
        if (!isset($urlWithKey[$schemaKey])) {
            return [];
        }

        $url = trim($urlWithKey[$schemaKey]);
        $map = null;
        try {
            $contents = $this->getClient()->getURL($url, $this->config()->get('timeout'));
            $map = json_decode($contents, true);
        } catch (Exception $e) {
            user_error($e->getMessage(), E_USER_WARNING);
            $map = [];
        }
        if (!is_array($map)) {
            $map = [];
        }

        $this->responseCache[$schemaKey] = $map;

        return $map;
    }

    /**
     * return a query given an ID
     *
     * @param string $queryID
     * @param string $schemaKey
     * @return string
     */
    public function getByID($queryID, $schemaKey = 'default')
    {
        $mapping = $this->getQueryMapping($schemaKey);

        return isset($mapping[$queryID]) ? $mapping[$queryID] : null;
    }

    /**
     * @param array $mapping
     * @return $this
     */
    public function setSchemaMapping(array $mapping)
    {
        foreach ($mapping as $schemaKey => $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException(
                    'setSchemaMapping accepts an array of schema keys to URLs'
                );
            }
        }

        // If the URLs have changed, stale the cache.
        $this->responseCache = [];

        $this->schemaToURL = $mapping;

        return $this;
    }

    /**
     * @return array
     */
    public function getSchemaMapping()
    {
        return $this->schemaToURL;
    }

    /**
     * @param HTTPClient $client
     * @return $this
     */
    public function setClient(HTTPClient $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return HTTPClient
     */
    public function getClient()
    {
        return $this->client;
    }
}
