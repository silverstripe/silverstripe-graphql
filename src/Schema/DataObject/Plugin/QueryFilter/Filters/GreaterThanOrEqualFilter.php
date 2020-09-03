<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters;

use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FieldFilterInterface;
use SilverStripe\ORM\Filterable;

/**
 * A query filter that filters records by greater than or equal comparison
 */
class GreaterThanOrEqualFilter implements FieldFilterInterface
{
    /**
     * @inheritdoc
     */
    public function apply(Filterable $list, string $fieldName, $value): iterable
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
