<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;

/**
 * A query filter that filters records by the end of a field's contents
 */
class EndsWithFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(iterable $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':EndsWith', $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'endswith';
    }
}
