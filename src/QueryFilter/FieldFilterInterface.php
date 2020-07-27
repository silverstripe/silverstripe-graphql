<?php


namespace SilverStripe\GraphQL\QueryFilter;

use SilverStripe\ORM\DataList;

/**
 * Defines the interface used by all read filters for scaffolded operations
 */
interface FieldFilterInterface
{
    /**
     * @param DataList $list
     * @param string $fieldName
     * @param string $value
     * @return DataList
     */
    public function apply(DataList $list, string $fieldName, $value): DataList;

    /**
     * @return string
     */
    public function getIdentifier(): string;
}
