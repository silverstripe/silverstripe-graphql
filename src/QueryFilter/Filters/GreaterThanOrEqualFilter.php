<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;

/**
 * A query filter that filters records by greater than or equal comparison
 */
class GreaterThanOrEqualFilter implements FieldFilterInterface
{
    /**
     * @inheritdoc
     */
    public function apply(iterable $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':GreaterThanOrEqual', $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'gte';
    }
}
