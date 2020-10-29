<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * For models that can provide a default set of fields
 */
interface DefaultFieldsProvider
{
    /**
     * @return array
     */
    public function getDefaultFields(): array;
}
