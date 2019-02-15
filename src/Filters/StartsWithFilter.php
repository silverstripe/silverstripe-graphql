<?php


namespace SilverStripe\GraphQL\Filters;

use SilverStripe\ORM\DataList;

class StartsWithFilter implements FilterInterface
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName . ':StartsWith', $value);
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName . ':StartsWith', $value);
    }

    public function getIdentifier()
    {
        return 'startswith';
    }

}