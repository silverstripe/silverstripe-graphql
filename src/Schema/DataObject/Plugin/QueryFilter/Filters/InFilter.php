<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters;

use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\ListFieldFilterInterface;
use SilverStripe\Model\List\SS_List;

/**
 * A query filter that filters records by the presence of a value in an array
 */
class InFilter implements ListFieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(SS_List $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName, (array) $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'in';
    }
}
