<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters;

use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FieldFilterInterface;
use SilverStripe\Model\List\SS_List;

/**
 * A query filter that filters records by the end of a field's contents
 */
class EndsWithFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(SS_List $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':EndsWith', $value);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'endswith';
    }
}
