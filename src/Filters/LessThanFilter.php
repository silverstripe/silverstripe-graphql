<?php


namespace SilverStripe\GraphQL\Filters;

use SilverStripe\ORM\DataList;

class LessThanFilter implements FilterInterface
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName . ':LessThan', $value);
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName . ':LessThan', $value);
    }

    public function getIdentifier()
    {
        return 'lt';
    }

}