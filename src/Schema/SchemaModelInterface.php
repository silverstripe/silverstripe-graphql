<?php


namespace SilverStripe\GraphQL\Schema;


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
     * @return array
     */
    public function getDefaultResolver(?array $context): array;

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
     * @return ModelAbstraction|null
     */
    public function getModelField(string $fieldName): ?ModelAbstraction;


}
