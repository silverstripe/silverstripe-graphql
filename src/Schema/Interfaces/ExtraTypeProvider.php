<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Type\Type;

/**
 * For models that provide extra types to the schema
 */
interface ExtraTypeProvider
{
    /**
     * @return Type[]
     */
    public function getExtraTypes(): array;
}
