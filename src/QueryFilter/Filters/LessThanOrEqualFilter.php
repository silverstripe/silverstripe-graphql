<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;

/**
 * A query filter that filters records by a less than or equal comparison
 */
class LessThanOrEqualFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(iterable $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':LessThanOrEqual', $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'lte';
    }
}
