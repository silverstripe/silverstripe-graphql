<?php

namespace SilverStripe\GraphQL\Tests\QueryFilter;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\QueryFilter\Filters\ContainsFilter;
use SilverStripe\GraphQL\QueryFilter\Filters\EndsWithFilter;
use SilverStripe\GraphQL\QueryFilter\Filters\EqualToFilter;
use SilverStripe\GraphQL\QueryFilter\FieldFilterInterface;
use SilverStripe\GraphQL\QueryFilter\Filters\GreaterThanFilter;
use SilverStripe\GraphQL\QueryFilter\Filters\GreaterThanOrEqualFilter;
use SilverStripe\GraphQL\QueryFilter\Filters\InFilter;
use SilverStripe\GraphQL\QueryFilter\Filters\LessThanFilter;
use SilverStripe\GraphQL\QueryFilter\Filters\LessThanOrEqualFilter;
use SilverStripe\GraphQL\QueryFilter\Filters\StartsWithFilter;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FilterDataList;

class FilterTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class,
    ];

    /**
     * @dataProvider provider
     * @param FieldFilterInterface $filter
     * @param $modifier
     * @param string $value
     */
    public function testFilterInclusion(FieldFilterInterface $filter, $modifier, $value = 'test')
    {
        $list = new FilterDataList(DataObjectFake::class);
        $filtered = $filter->applyInclusion($list, 'MyField', 'test');
        $this->assertEquals('MyField:' . $modifier, $filtered->filterField);
        $this->assertEquals($value, $filtered->filterValue);
    }

    /**
     * @dataProvider provider
     * @param FieldFilterInterface $filter
     * @param string $modifier
     */
    public function testFilterExclusion(FieldFilterInterface $filter, $modifier, $value = 'test')
    {
        $list = new FilterDataList(DataObjectFake::class);
        $filtered = $filter->applyExclusion($list, 'MyField', 'test');
        $this->assertEquals('MyField:' . $modifier, $filtered->excludeField);
        $this->assertEquals($value, $filtered->excludeValue);
    }

    /**
     * @return array
     */
    public function provider()
    {
        return [
            [new ContainsFilter(), 'PartialMatch'],
            [new EndsWithFilter(), 'EndsWith'],
            [new StartsWithFilter(), 'StartsWith'],
            [new EqualToFilter(), 'ExactMatch'],
            [new GreaterThanFilter(), 'GreaterThan'],
            [new GreaterThanOrEqualFilter(), 'GreaterThanOrEqual'],
            [new LessThanFilter(), 'LessThan'],
            [new LessThanOrEqualFilter(), 'LessThanOrEqual'],
            [new InFilter(), 'ExactMatch', ['test']],
        ];
    }
}
