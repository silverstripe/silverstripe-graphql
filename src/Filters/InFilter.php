<?php


namespace SilverStripe\GraphQL\Filters;

use SilverStripe\ORM\DataList;

class InFilter implements ListFilterInterface
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName, (array) $value);
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName, (array) $value);
    }

    public function getIdentifier()
    {
        return 'in';
    }

}