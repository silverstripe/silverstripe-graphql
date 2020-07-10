<?php


namespace SilverStripe\GraphQL\Schema;


interface InputTypeProvider
{
    /**
     * @param SchemaModelInterface $model
     * @param string $typeName
     * @param array|null $config
     * @return TypeAbstraction[]
     */
    public function provideInputTypes(
        SchemaModelInterface $model,
        string $typeName,
        ?array $config = null
    ): array;
}
