<?php


namespace SilverStripe\GraphQL\QueryFilter\Filters;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;
use SilverStripe\ORM\DataList;

class StartsWithFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(DataList $list, string $fieldName, $value): DataList
    {
        return $list->filter($fieldName . ':StartsWith', $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'startswith';
    }
}
