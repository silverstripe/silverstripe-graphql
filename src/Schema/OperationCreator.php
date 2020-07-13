<?php


namespace SilverStripe\GraphQL\Schema;


interface OperationCreator
{
    /**
     * @param SchemaModelInterface $model
     * @param string $typeName
     * @param array $config
     * @return FieldAbstraction
     */
    public function createOperation(
        SchemaModelInterface $model,
        string $typeName,
        array $config = []
    ): FieldAbstraction;
}
