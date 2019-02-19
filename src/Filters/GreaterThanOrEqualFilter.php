<?php


namespace SilverStripe\GraphQL\Filters;

use SilverStripe\ORM\DataList;

class GreaterThanOrEqualFilter implements FilterInterface
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName . ':GreaterThanOrEqual', $value);
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName . ':GreaterThanOrEqual', $value);
    }

    public function getIdentifier()
    {
        return 'gte';
    }

}