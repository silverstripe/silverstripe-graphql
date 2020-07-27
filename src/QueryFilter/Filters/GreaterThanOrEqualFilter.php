<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;
use SilverStripe\ORM\DataList;

class GreaterThanOrEqualFilter implements FieldFilterInterface
{
    public function apply(DataList $list, string $fieldName, $value): DataList
    {
        return $list->filter($fieldName . ':GreaterThanOrEqual', $value);
    }

    public function getIdentifier(): string
    {
        return 'gte';
    }
}
