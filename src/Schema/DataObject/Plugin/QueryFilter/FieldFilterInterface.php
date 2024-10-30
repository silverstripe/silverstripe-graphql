<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter;

use SilverStripe\ORM\DataList;
use SilverStripe\Model\List\SS_List;

/**
 * Defines the interface used by all read filters for operations
 */
interface FieldFilterInterface
{
    public function apply(SS_List $list, string $fieldName, $value): iterable;

    /**
     * @return string
     */
    public function getIdentifier(): string;
}
