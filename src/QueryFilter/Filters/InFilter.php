<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\ListFieldFilterInterface;
use SilverStripe\ORM\DataList;

class InFilter implements ListFieldFilterInterface
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName . ':ExactMatch', (array) $value);
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName . ':ExactMatch', (array) $value);
    }

    public function getIdentifier()
    {
        return 'in';
    }
}
