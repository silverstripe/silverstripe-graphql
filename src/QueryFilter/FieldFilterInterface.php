<?php


namespace SilverStripe\GraphQL\QueryFilter;

use SilverStripe\ORM\DataList;

/**
 * Defines the interface used by all read filters for scaffolded operations
 */
interface FieldFilterInterface
{
    /**
     * @param iterable $list
     * @param string $fieldName
     * @param string $value
     * @return DataList
     */
    public function apply(iterable $list, string $fieldName, $value): iterable;

    /**
     * @return string
     */
    public function getIdentifier(): string;
}
