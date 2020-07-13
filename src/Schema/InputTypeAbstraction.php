<?php


namespace SilverStripe\GraphQL\Schema;


class InputTypeAbstraction extends TypeAbstraction
{
    private $isInput = true;

    public function getIsInput(): bool
    {
        return true;
    }
}
