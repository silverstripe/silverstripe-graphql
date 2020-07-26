<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Resolver\ResolverReference;
use SilverStripe\GraphQL\Schema\Type\ModelType;

interface SchemaModelInterface
{
    /**
     * @param string $fieldName
     * @return bool
     */
    public function hasField(string $fieldName): bool;

    /**
     * @param string $fieldName
     * @return string|null
     */
    public function getTypeForField(string $fieldName): ?string;

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
    public function getModelField(string $fieldName): ?ModelType;


}
