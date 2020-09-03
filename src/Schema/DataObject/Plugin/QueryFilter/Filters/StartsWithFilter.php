<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters;

use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FieldFilterInterface;
use SilverStripe\ORM\Filterable;

/**
 * A query filter that filters records by the start of a field's content
 */
class StartsWithFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(Filterable $list, string $fieldName, $value): iterable
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
