<?php

namespace SilverStripe\GraphQL\PersistedQuery;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * Class FileProvider
 * @package SilverStripe\GraphQL\PersistedQuery
 */
class FileProvider implements PersistedQueryMappingProvider
{
    use Configurable, Injectable;

    /**
     * Example:
     * <code>
     * SilverStripe\GraphQL\PersistedQuery\FileProvider:
     *   path_with_key:
     *     default: '/var/www/project/persisted-graphql-query-mapping.json'
     * </code>
     *
     * Note: The mapping supports multi-schema feature, you can have other schemaKey rather than 'default'
     *
     * @var array
     * @config
     */
    private static $path_with_key = [
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
        $pathWithKey = $this->config()->path_with_key;
        if (!isset($pathWithKey[$schemaKey])) {
            return [];
        }

        $path = trim($pathWithKey[$schemaKey]);
        $contents = trim(file_get_contents($path));
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
