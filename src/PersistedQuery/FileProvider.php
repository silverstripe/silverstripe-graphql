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
     * SilverStripe\Core\Injector\Injector:
     *   SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
     *     class: SilverStripe\GraphQL\PersistedQuery\FileProvider:
     *       properties:
     *         schemaMapping:
     *           default: '/var/www/project/persisted-graphql-query-mapping.json'
     * </code>
     *
     * Note: The mapping supports multi-schema feature, you can have other schemaKey rather than 'default'
     *
     * @var array
     * @config
     */
    protected $schemaToPath = [
        'default' => ''
    ];

    /**
     * return a map from <id> to <query>
     *
     * @param string $schemaKey
     * @return array
     */
    public function getQueryMapping($schemaKey = 'default')
    {
        /** @noinspection PhpUndefinedFieldInspection */
        /** @noinspection StaticInvocationViaThisInspection */
        $pathWithKey = $this->getSchemaMapping();
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
        $this->schemaToPath = $mapping;

        return $this;
    }

    /**
     * @return array
     */
    public function getSchemaMapping()
    {
        return $this->schemaToPath;
    }
}
