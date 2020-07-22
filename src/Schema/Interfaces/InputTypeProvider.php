<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Type\InputType;

interface InputTypeProvider
{
    /**
     * @param SchemaModelInterface $model
     * @param string $typeName
     * @param array $config
     * @return InputType[]
     */
    public function provideInputTypes(
        SchemaModelInterface $model,
        string $typeName,
        array $config = []
    ): array;
}
