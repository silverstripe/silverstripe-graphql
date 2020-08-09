<?php


namespace SilverStripe\GraphQL\Schema\Type;

/**
 * Abstraction for input types
 */
class InputType extends Type
{
    public function getIsInput(): bool
    {
        return true;
    }
}
