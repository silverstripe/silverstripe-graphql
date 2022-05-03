<?php


namespace SilverStripe\GraphQL\PersistedQuery;

use InvalidArgumentException;
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
     * SilverStripe\Core\Injector\Injector:
     *   SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider:
     *     class: SilverStripe\GraphQL\PersistedQuery\JSONStringProvider:
     *       properties:
     *         schemaMapping:
     *           default: '{"uuid-1":"query{ID+Email}"}'
     * </code>
     *
     * Note: The mapping supports multi-schema feature, you can have other schemaKey rather than 'default'
     *
     * @config
     */
    protected array $schemaToJSON = [
        'default' => ''
    ];

    /**
     * return a map from <id> to <query>
     */
    public function getQueryMapping(string $schemaKey = 'default'): array
    {
        /** @noinspection PhpUndefinedFieldInspection */
        /** @noinspection StaticInvocationViaThisInspection */
        $mappingWithKey = $this->getSchemaMapping();
        if (!isset($mappingWithKey[$schemaKey])) {
            return [];
        }

        $mapping = $mappingWithKey[$schemaKey];
        $result = json_decode($mapping ?? '', true);
        if (!is_array($result)) {
            return [];
        }

        return $result;
    }

    /**
     * return a query given an ID
     */
    public function getByID(string $queryID, string $schemaKey = 'default'): ?string
    {
        $mapping = $this->getQueryMapping($schemaKey);

        return isset($mapping[$queryID]) ? $mapping[$queryID] : null;
    }

    public function setSchemaMapping(array $mapping): self
    {
        foreach ($mapping as $schemaKey => $queryMap) {
            if (!is_string($queryMap)) {
                throw new InvalidArgumentException(
                    'setSchemaMapping accepts an array of schema keys to JSON strings'
                );
            }
            if (json_decode($queryMap ?? '') === null) {
                throw new InvalidArgumentException(
                    'setSchemaMapping passed an invalid string of JSON. Got error: ' . json_last_error()
                );
            }
        }

        $this->schemaToJSON = $mapping;

        return $this;
    }

    public function getSchemaMapping(): array
    {
        return $this->schemaToJSON;
    }
}
