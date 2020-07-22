<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Type\Type;

interface ExtraTypeProvider
{
    /**
     * @return Type[]
     */
    public function getExtraTypes(): array;
}
