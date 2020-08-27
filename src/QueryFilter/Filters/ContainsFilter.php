<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;

/**
 * A filter that selects records that partially match a keyword
 */
class ContainsFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(iterable $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':PartialMatch', $value);
    }
    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'contains';
    }
}
