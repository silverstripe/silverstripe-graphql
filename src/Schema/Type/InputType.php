<?php


namespace SilverStripe\GraphQL\Schema\Type;

/**
 * Abstraction that can express input types as code
 */
class InputType extends Type
{
    public function getIsInput(): bool
    {
        return true;
    }
}
