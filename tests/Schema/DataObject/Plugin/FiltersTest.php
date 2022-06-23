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
use SilverStripe\ORM\ArrayList;

class FiltersTest extends SapphireTest
{
    private array $values = [
        'string' => 'test',
        'array' => ['a', 'b'],
        'number' => 42,
        'null' => null
    ];

    /**
     * @dataProvider filterArgumentsProvider
     */
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

    public function filterArgumentsProvider(): array
    {
        return [
            [
                new ContainsFilter(),
                'contains',
                array_slice($this->values, 0, 2, true)
            ],
            [
                new EndsWithFilter(),
                'endswith',
                array_slice($this->values, 0, 2, true)
            ],
            [
                new EqualToFilter(),
                'eq',
                $this->values
            ],
            [
                new GreaterThanFilter(),
                'gt',
                $this->values
            ],
            [
                new GreaterThanOrEqualFilter(),
                'gte',
                $this->values
            ],
            [
                new InFilter(),
                'in',
                $this->values['array']
            ],
            [
                new LessThanFilter(),
                'lt',
                $this->values
            ],
            [
                new LessThanOrEqualFilter(),
                'lte',
                $this->values
            ],
            [
                new NotEqualFilter(),
                'ne',
                $this->values
            ],
            [
                new StartsWithFilter(),
                'startswith',
                array_slice($this->values, 0, 2, true)
            ]
        ];
    }
}
