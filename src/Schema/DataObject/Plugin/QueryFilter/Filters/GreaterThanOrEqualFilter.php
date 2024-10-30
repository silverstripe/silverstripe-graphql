<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters;

use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FieldFilterInterface;
use SilverStripe\Model\List\SS_List;

/**
 * A query filter that filters records by greater than or equal comparison
 */
class GreaterThanOrEqualFilter implements FieldFilterInterface
{
    /**
     * @inheritdoc
     */
    public function apply(SS_List $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':GreaterThanOrEqual', $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'gte';
    }
}
