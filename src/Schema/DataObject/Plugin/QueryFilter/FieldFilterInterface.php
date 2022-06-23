<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\Filterable;

/**
 * Defines the interface used by all read filters for operations
 */
interface FieldFilterInterface
{
    public function apply(Filterable $list, string $fieldName, $value): iterable;

    /**
     * @return string
     */
    public function getIdentifier(): string;
}
