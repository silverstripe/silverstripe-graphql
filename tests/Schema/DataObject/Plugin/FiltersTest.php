<?php

namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\FieldFilterInterface;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters\ContainsFilter;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters\EndsWithFilter;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters\EqualToFilter;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters\GreaterThanFilter;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters\GreaterThanOrEqualFilter;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters\InFilter;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters\LessThanFilter;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters\LessThanOrEqualFilter;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters\NotEqualFilter;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter\Filters\StartsWithFilter;
use SilverStripe\Model\List\ArrayList;
use PHPUnit\Framework\Attributes\DataProvider;

class FiltersTest extends SapphireTest
{
    /**
     * @internal
     */
    private static array $values = [
        'string' => 'test',
        'array' => ['a', 'b'],
        'number' => 42,
        'null' => null
    ];

    #[DataProvider('filterArgumentsProvider')]
    public function testFilterArguments(FieldFilterInterface $filter, string $identifier, array $params): void
    {
        $this->assertEquals($identifier, $filter->getIdentifier());
        $list = new ArrayList();
        foreach ($params as $key => $value) {
            $this->assertTrue(
                is_iterable($filter->apply($list, 'field', $value)),
                sprintf('%s should accept %s as value', get_class($filter), $key)
            );
        }
    }

    public static function filterArgumentsProvider(): array
    {
        return [
            [
                new ContainsFilter(),
                'contains',
                array_slice(FiltersTest::$values, 0, 2, true)
            ],
            [
                new EndsWithFilter(),
                'endswith',
                array_slice(FiltersTest::$values, 0, 2, true)
            ],
            [
                new EqualToFilter(),
                'eq',
                FiltersTest::$values
            ],
            [
                new GreaterThanFilter(),
                'gt',
                FiltersTest::$values
            ],
            [
                new GreaterThanOrEqualFilter(),
                'gte',
                FiltersTest::$values
            ],
            [
                new InFilter(),
                'in',
                FiltersTest::$values['array']
            ],
            [
                new LessThanFilter(),
                'lt',
                FiltersTest::$values
            ],
            [
                new LessThanOrEqualFilter(),
                'lte',
                FiltersTest::$values
            ],
            [
                new NotEqualFilter(),
                'ne',
                FiltersTest::$values
            ],
            [
                new StartsWithFilter(),
                'startswith',
                array_slice(FiltersTest::$values, 0, 2, true)
            ]
        ];
    }
}
