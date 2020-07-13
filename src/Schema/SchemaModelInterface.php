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
     * @return array
     */
    public function getDefaultFields(): array;

    /**
     * @return callable
     */
    public function getDefaultResolver(): callable;

    /**
     * @return string
     */
    public function getSourceClass(): string;

    /**
     * @return array
     */
    public function getAllFields(): array;


}
