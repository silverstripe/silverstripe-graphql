<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;
use SilverStripe\ORM\DataList;

class CustomEqFilter implements FieldFilterInterface
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list->filter($fieldName, 'bob');
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list->exclude($fieldName, 'bob');
    }

    public function getIdentifier()
    {
        return 'eq';
    }
}
