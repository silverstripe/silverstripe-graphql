<?php


namespace SilverStripe\GraphQL\Filters;


use SilverStripe\ORM\DataList;

/**
 * Defines the interface used by all read filters for scaffolded operations
 */
interface FilterInterface
{
    const SEPARATOR = '__';

    /**
     * @param DataList $list
     * @param string $fieldName
     * @param string $value
     * @return DataList
     */
    public function applyInclusion(DataList $list, $fieldName, $value);

    /**
     * @param DataList $list
     * @param string $fieldName
     * @param string $value
     * @return DataList
     */
    public function applyExclusion(DataList $list, $fieldName, $value);

    /**
     * @return string
     */
    public function getIdentifier();
}