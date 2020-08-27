<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;

/**
 * A query filter that filters records by a less than comparison
 */
class LessThanFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(iterable $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':LessThan', $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'lt';
    }
}
