<?php


namespace SilverStripe\GraphQL\Filters;

use SilverStripe\ORM\DataList;

class EqualToFilter implements FilterInterface
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName . ':ExactMatch', $value);
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName . ':ExactMatch', $value);
    }

    public function getIdentifier()
    {
        return 'eq';
    }
}