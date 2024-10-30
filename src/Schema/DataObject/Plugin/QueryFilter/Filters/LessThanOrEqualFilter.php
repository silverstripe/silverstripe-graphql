<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters;

use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FieldFilterInterface;
use SilverStripe\Model\List\SS_List;

/**
 * A query filter that filters records by a less than or equal comparison
 */
class LessThanOrEqualFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(SS_List $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':LessThanOrEqual', $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'lte';
    }
}
