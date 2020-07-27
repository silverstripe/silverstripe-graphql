<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\ListFieldFilterInterface;
use SilverStripe\ORM\DataList;

class InFilter implements ListFieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(DataList $list, string $fieldName, $value): DataList
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
