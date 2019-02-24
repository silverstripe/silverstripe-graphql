<?php

namespace SilverStripe\GraphQL\Tests\Filters;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Filters\ContainsFilter;
use SilverStripe\GraphQL\Filters\EndsWithFilter;
use SilverStripe\GraphQL\Filters\EqualToFilter;
use SilverStripe\GraphQL\Filters\FilterInterface;
use SilverStripe\GraphQL\Filters\GreaterThanFilter;
use SilverStripe\GraphQL\Filters\GreaterThanOrEqualFilter;
use SilverStripe\GraphQL\Filters\InFilter;
use SilverStripe\GraphQL\Filters\LessThanFilter;
use SilverStripe\GraphQL\Filters\LessThanOrEqualFilter;
use SilverStripe\GraphQL\Filters\StartsWithFilter;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FilterDataList;

class FilterTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DataObjectFake::class,
    ];

    /**
     * @dataProvider provider
     * @param FilterInterface $filter
     * @param $modifier
     * @param string $value
     */
    public function testFilterInclusion(FilterInterface $filter, $modifier, $value = 'test')
    {
        $list = new FilterDataList(DataObjectFake::class);
        $filtered = $filter->applyInclusion($list, 'MyField', 'test');
        $this->assertEquals('MyField:' . $modifier, $filtered->filterField);
        $this->assertEquals($value, $filtered->filterValue);
    }

    /**
     * @dataProvider provider
     * @param FilterInterface $filter
     * @param string $modifier
     */
    public function testFilterExclusion(FilterInterface $filter, $modifier, $value = 'test')
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