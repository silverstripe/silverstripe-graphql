<?php


namespace SilverStripe\GraphQL\Filters;

use SilverStripe\ORM\DataList;

class LessThanOrEqualFilter implements FilterInterface
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName . ':LessThanOrEqual', $value);
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName . ':LessThanOrEqual', $value);
    }

    public function getIdentifier()
    {
        return 'lte';
    }

}