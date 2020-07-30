<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;

class GreaterThanOrEqualFilter implements FieldFilterInterface
{
    public function apply(iterable $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':GreaterThanOrEqual', $value);
    }

    public function getIdentifier(): string
    {
        return 'gte';
    }
}
