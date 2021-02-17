<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Config\ModelConfiguration;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\ModelType;

/**
 * Implementors of this interface can be models that generate types and operations
 */
interface SchemaModelInterface
{
    /**
     * @return string
     */
    public static function getIdentifier(): string;

    /**
     * @param string $fieldName
     * @return bool
     */
    public function hasField(string $fieldName): bool;

    /**
     * @param string $fieldName
     * @param array $config
     * @return ModelField|null
     */
    public function getField(string $fieldName, array $config = []): ?ModelField;

    /**
     * @return string
     */
    public function getTypeName(): string;

    /**
     * @param array|null $context
     * @return ResolverReference
     */
    public function getDefaultResolver(?array $context = []): ResolverReference;

    /**
     * @return string
     */
    public function getSourceClass(): string;

    /**
     * @return array
     */
    public function getAllFields(): array;

    /**
     * @param string $fieldName
     * @return ModelType|null
     */
    public function getModelTypeForField(string $fieldName): ?ModelType;

    /**
     * @return SchemaConfig
     */
    public function getSchemaConfig(): SchemaConfig;

    /**
     * @return ModelConfiguration|null
     */
    public function getModelConfiguration(): ?ModelConfiguration;

    /**
     * @param string $field
     * @return string
     */
    public function getPropertyForField(string $field): string;
}
