<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\MutationCreator;
use SilverStripe\ORM\DataList;

class FilterDataList extends DataList implements TestOnly
{
    public $filterField;

    public $filterValue;

    public $excludeField;

    public $excludeValue;

    public function filter()
    {
        $args = func_get_args();
        $field = $args[0];
        $value = $args[1];

        $clone = clone $this;
        $clone->filterField = $field;
        $clone->filterValue = $value;

        return $clone;
    }

    public function exclude()
    {
        $args = func_get_args();
        $field = $args[0];
        $value = $args[1];

        $clone = clone $this;

        $clone->excludeField = $field;
        $clone->excludeValue = $value;

        return $clone;
    }
}
