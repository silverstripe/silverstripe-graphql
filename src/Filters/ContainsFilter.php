<?php


namespace SilverStripe\GraphQL\Filters;

use SilverStripe\ORM\DataList;

class ContainsFilter implements FilterInterface
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName . ':PartialMatch', $value);
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName . ':PartialMatch', $value);
    }

    public function getIdentifier()
    {
        return 'contains';
    }

}