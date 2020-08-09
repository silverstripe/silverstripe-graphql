<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * Implementors of this interface can create queries an mutations dynamically
 */
interface OperationCreator
{
    /**
     * @param SchemaModelInterface $model
     * @param string $typeName
     * @param array $config
     * @return ModelOperation|null
     */
    public function createOperation(
        SchemaModelInterface $model,
        string $typeName,
        array $config = []
    ): ?ModelOperation;
}
