<?php

namespace SilverStripe\GraphQL\Config;

use SilverStripe\Core\ClassInfo;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;

class ModelConfiguration extends Configuration
{
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
     * @throws SchemaBuilderException
     */
    public function getTypeName(string $class, array $mapping = []): string
    {
        $typeName = $this->formatClass($class);
        $prefix = $this->getPrefix($class);

        return $prefix . $typeName;
    }

    /**
     * Fields that are added to the model by default. Can be opted out per type
     * @return array
     * @throws SchemaBuilderException
     */
    public function getDefaultFields(): array
    {
        return $this->get('default_fields', []);
    }

    /**
     * Fields that will appear on all models. Cannot be opted out on any type.
     * @return array
     * @throws SchemaBuilderException
     */
    public function getBaseFields(): array
    {
        return $this->get('base_fields', []);
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
