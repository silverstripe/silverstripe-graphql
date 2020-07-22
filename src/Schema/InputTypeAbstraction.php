<?php


namespace SilverStripe\GraphQL\Schema;


class InputTypeAbstraction extends TypeAbstraction
{
    public function getIsInput(): bool
    {
        return true;
    }
}
