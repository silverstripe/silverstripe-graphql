<?php

namespace SilverStripe\GraphQL\PersistedQuery;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * Class HTTPProvider
 * @package SilverStripe\GraphQL\PersistedQuery
 */
class HTTPProvider implements PersistedQueryMappingProvider
{
    use Configurable, Injectable;

    /**
     * Example:
     * <code>
     * SilverStripe\GraphQL\PersistedQuery\HTTPProvider:
     *   url_with_key:
     *     default: 'http://example.com/mapping.json'
     * </code>
     *
     * Note: The mapping supports multi-schema feature, you can have other schemaKey rather than 'default'
     *
     * @var array
     * @config
     */
    private static $url_with_key = [
        'default' => ''
    ];

    /**
     * return a map from <query> to <id>
     *
     * @param string $schemaKey
     * @return array
     */
    public function getMapping($schemaKey = 'default')
    {
        /** @noinspection PhpUndefinedFieldInspection */
        /** @noinspection StaticInvocationViaThisInspection */
        $urlWithKey = $this->config()->url_with_key;
        if (!isset($urlWithKey[$schemaKey])) {
            return [];
        }

        $url = trim($urlWithKey[$schemaKey]);
        $request = new Request('GET', $url);
        $client = new Client();
        try {
            $response = $client->send($request, ['timeout' => 5]);
            $contents = trim($response->getBody()->getContents());
        } catch (\RuntimeException $e) {
            user_error($e->getMessage(), E_USER_WARNING);
            return [];
        } catch (GuzzleException $e) {
            user_error($e->getMessage(), E_USER_WARNING);
            return [];
        }

        $result = json_decode($contents, true);
        if (!is_array($result)) {
            return [];
        }

        return $result;
    }

    /**
     * return a map from <id> to <query>
     *
     * @param string $schemaKey
     * @return array
     */
    public function getInvertedMapping($schemaKey = 'default')
    {
        return array_flip($this->getMapping($schemaKey));
    }
}
