<?php


namespace SilverStripe\GraphQL\Schema;


interface SchemaModelInterface
{
    public function hasField(string $fieldName): bool;

    public function getTypeForField(string $fieldName): ?string;

    public function getDefaultFields(): array;

    public function getDefaultResolver(): callable;

    public function getSourceClass(): string;


}
