<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\GraphQL\Filters\FilterInterface;
use SilverStripe\ORM\DataList;

class FakeFilter implements FilterInterface
{
    public function applyInclusion(DataList $list, $fieldName, $value)
    {
        return $list;
    }

    public function applyExclusion(DataList $list, $fieldName, $value)
    {
        return $list;
    }

    public function getIdentifier()
    {
        return 'fake';
    }
}
