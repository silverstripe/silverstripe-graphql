<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters;

use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FieldFilterInterface;
use SilverStripe\ORM\Filterable;

/**
 * A filter that selects records that partially match a keyword
 */
class ContainsFilter implements FieldFilterInterface
{
    /**
     * @inheritDoc
     */
    public function apply(Filterable $list, string $fieldName, $value): iterable
    {
        return $list->filter($fieldName . ':PartialMatch', $value);
    }
    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'contains';
    }
}
