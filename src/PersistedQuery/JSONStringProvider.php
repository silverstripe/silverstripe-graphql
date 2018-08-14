<?php


namespace SilverStripe\GraphQL\PersistedQuery;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * Class ConfigStringProvider
 * @package SilverStripe\GraphQL\PersistedQuery
 *
 * Load mapping from json string in the config file
 */
class JSONStringProvider implements PersistedQueryMappingProvider
{
    use Configurable, Injectable;

    /**
     * Example:
     * <code>
     * SilverStripe\GraphQL\PersistedQuery\JSONStringProvider:
     *   mapping_with_key:
     *     default: '{"query{ID+Email}":"uuid-1"}'
     * </code>
     *
     * Note: The mapping supports multi-schema feature, you can have other schemaKey rather than 'default'
     *
     * @var array
     * @config
     */
    private static $mapping_with_key = [
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
        $mappingWithKey = $this->config()->mapping_with_key;
        if (!isset($mappingWithKey[$schemaKey])) {
            return [];
        }

        $mapping = $mappingWithKey[$schemaKey];
        $result = json_decode($mapping, true);
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
