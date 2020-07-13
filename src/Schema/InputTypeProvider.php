<?php


namespace SilverStripe\GraphQL\Schema;


interface InputTypeProvider
{
    /**
     * @param SchemaModelInterface $model
     * @param string $typeName
     * @param array $config
     * @return InputTypeAbstraction[]
     */
    public function provideInputTypes(
        SchemaModelInterface $model,
        string $typeName,
        array $config = []
    ): array;
}
