<?php


namespace SilverStripe\GraphQL\Filters;

use SilverStripe\ORM\DataList;

class EndsWithFilter
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName . ':EndsWith', $value);
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName . ':EndsWith', $value);
    }

    public function getIdentifier()
    {
        return 'endswith';
    }

}