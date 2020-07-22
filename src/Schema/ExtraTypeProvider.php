<?php


namespace SilverStripe\GraphQL\Schema;


interface ExtraTypeProvider
{
    /**
     * @return TypeAbstraction[]
     */
    public function getExtraTypes(): array;
}
