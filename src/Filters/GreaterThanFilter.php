<?php


namespace SilverStripe\GraphQL\Filters;

use SilverStripe\ORM\DataList;

class GreaterThanFilter implements FilterInterface
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName . ':GreaterThan', $value);
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName . ':GreaterThan', $value);
    }

    public function getIdentifier()
    {
        return 'gt';
    }

}