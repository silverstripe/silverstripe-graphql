<?php


namespace SilverStripe\GraphQL\Filters;

use SilverStripe\ORM\DataList;

class LessThanOrEqualFilter
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName . ':GreaterThanEqual', $value);
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName . ':GreaterThanEqual', $value);
    }

    public function getIdentifier()
    {
        return 'gte';
    }

}