<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;

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
