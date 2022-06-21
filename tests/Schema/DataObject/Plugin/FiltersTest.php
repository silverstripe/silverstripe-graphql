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

    public function testFilterArguments()
    {
        // Contains
        $this->testFilter(
            new ContainsFilter(),
            'contains',
            array_slice($this->values, 0, 2, true)
        );

        // Endswith
        $this->testFilter(
            new EndsWithFilter(),
            'endswith',
            array_slice($this->values, 0, 2, true)
        );

        // EqualToFilter
        $this->testFilter(
            new EqualToFilter(),
            'eq',
            $this->values
        );

        // GreaterThanFilter
        $this->testFilter(
            new GreaterThanFilter(),
            'gt',
            $this->values
        );

        // GreaterThanFilter
        $this->testFilter(
            new GreaterThanOrEqualFilter(),
            'gte',
            $this->values
        );

        // InFilter
        $this->testFilter(
            new InFilter(),
            'in',
            $this->values['array']
        );

        // LessThanFilter
        $this->testFilter(
            new LessThanFilter(),
            'lt',
            $this->values
        );

        // LessThanFilter
        $this->testFilter(
            new LessThanOrEqualFilter(),
            'lte',
            $this->values
        );

        // NotEqualFilter
        $this->testFilter(
            new NotEqualFilter(),
            'ne',
            $this->values
        );

        // StartsWithFilter
        $this->testFilter(
            new StartsWithFilter(),
            'startswith',
            array_slice($this->values, 0, 2, true)
        );
    }

    private function testFilter(FieldFilterInterface $filter, string $identifier, array $params)
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
}
