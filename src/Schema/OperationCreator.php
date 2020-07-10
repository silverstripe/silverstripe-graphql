<?php


namespace SilverStripe\GraphQL\Schema;


interface OperationCreator
{
    public function createOperation(
        SchemaModelInterface $model,
        string $typeName,
        ?array $config = null
    ): FieldAbstraction;
}
