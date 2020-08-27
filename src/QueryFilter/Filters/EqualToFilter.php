<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;

/**
 * A query filter that filters records by exact match of a keyword
 */
class EqualToFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(iterable $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':ExactMatch', $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'eq';
    }
}
