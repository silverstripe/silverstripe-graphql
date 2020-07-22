<?php


namespace SilverStripe\GraphQL\Schema\Type;


class InputType extends Type
{
    public function getIsInput(): bool
    {
        return true;
    }
}
