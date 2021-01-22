<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\GraphQL\Schema\Type\ModelType;

/**
 * Implementors of this interface provide input types back to the schema
 */
interface InputTypeProvider
{
    /**
     * @param ModelType $modelType
     * @param array $config
     * @return InputType[]
     */
    public function provideInputTypes(
        ModelType $modelType,
        array $config = []
    ): array;
}
