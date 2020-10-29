<?php

namespace SilverStripe\GraphQL\Config;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\ModelConfigurationProvider;
use SilverStripe\GraphQL\Schema\Registry\SchemaModelCreatorRegistry;
use SilverStripe\GraphQL\Schema\Schema;

class ModelConfiguration
{
    use Injectable;

    /**
     * @var array
     */
    private $settings = [];

    /**
     * ModelConfiguration constructor.
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    /**
     * @return callable|null
     * @throws SchemaBuilderException
     */
    public function getTypeFormatter(): ?callable
    {
        return $this->get('type_formatter', [ClassInfo::class, 'shortName']);
    }

    /**
     * @return string
     * @throws SchemaBuilderException
     */
    public function getTypePrefix(): string
    {
        return $this->get('type_prefix', '');
    }

    /**
     * @return array
     * @throws SchemaBuilderException
     */
    public function getNestedQueryPlugins(): array
    {
        return $this->get('nested_query_plugins', []);
    }

    /**
     * @param string $operation
     * @return array
     * @throws SchemaBuilderException
     */
    public function getOperationConfig(string $operation): array
    {
        return $this->get(['operations', $operation], []);
    }

    /**
     * @param string $class
     * @return string
     * @throws SchemaBuilderException
     */
    public function getTypeName(string $class): string
    {
        $mapping = $this->get('type_mapping', []);
        $custom = $mapping[$class] ?? null;
        if ($custom) {
            return $custom;
        }

        $typeName = $this->formatClass($class);
        $prefix = $this->getPrefix($class);

        return $prefix . $typeName;
    }

    /**
     * Return a setting by dot.separated.syntax
     * @param string|array $path
     * @param mixed $default
     * @return array|string|bool|null
     * @throws SchemaBuilderException
     */
    public function get($path, $default = null)
    {
        Schema::invariant(
            is_array($path) || is_string($path),
            'get() must be passed an array or string'
        );
        $parts = is_string($path) ? explode('.', $path) : $path;
        $scope = $this->settings;
        foreach ($parts as $part) {
            $scope = $scope[$part] ?? $default;
            if (!is_array($scope)) {
                break;
            }
        }

        return $scope;
    }


    /**
     * @param string $class
     * @return string
     * @throws SchemaBuilderException
     */
    private function formatClass(string $class): string
    {
        $formatter = $this->getTypeFormatter();
        Schema::invariant(
            is_callable($formatter, false),
            'type_formatter property for %s is not callable',
            __CLASS__
        );

        return call_user_func_array($formatter, [$class]);
    }

    /**
     * @param string $class
     * @return string
     * @throws SchemaBuilderException
     */
    private function getPrefix(string $class): string
    {
        $prefix = $this->getTypePrefix();
        if (is_callable($prefix, false)) {
            return call_user_func_array($prefix, [$class]);
        }

        Schema::invariant(
            is_string($prefix),
            'type_prefix on %s must be a string',
            __CLASS__
        );

        return $prefix;
    }
}
