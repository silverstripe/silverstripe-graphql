<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Type\InputType;

/**
 * Implementors of this interface provide input types back to the schema
 */
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
