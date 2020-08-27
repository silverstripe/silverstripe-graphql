<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;


use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;

/**
 * A query filter that filters records by negating an exact match
 */
class NotEqualFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(iterable $list, string $fieldName, $value): iterable
    {
        return $list->exclude($fieldName, $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'ne';
    }

}
