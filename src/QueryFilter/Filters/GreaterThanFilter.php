<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;
use SilverStripe\ORM\DataList;

class GreaterThanFilter implements FieldFilterInterface
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
