<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\ListFieldFilterInterface;

class InFilter implements ListFieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(iterable $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':ExactMatch', (array) $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'in';
    }
}
