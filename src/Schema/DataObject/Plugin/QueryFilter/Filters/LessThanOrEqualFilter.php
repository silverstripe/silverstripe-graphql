<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters;

use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FieldFilterInterface;
use SilverStripe\ORM\Filterable;

/**
 * A query filter that filters records by a less than or equal comparison
 */
class LessThanOrEqualFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(Filterable $list, string $fieldName, $value): iterable
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
