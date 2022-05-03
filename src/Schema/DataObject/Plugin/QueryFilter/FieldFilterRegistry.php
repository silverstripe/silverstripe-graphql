<?php

namespace SilverStripe\GraphQL\Schema\DataObject\Plugin\QueryFilter;

use InvalidArgumentException;

class FieldFilterRegistry implements FilterRegistryInterface
{
    /**
     * @var array FieldFilterInterface[]
     */
    protected array $filters = [];

    /**
     * FilterRegistry constructor.
     * @param mixed ...$filters
     */
    public function __construct(...$filters)
    {
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }
    }

    /**
     * @param FieldFilterInterface $filter
     * @param string|null $identifier
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addFilter(FieldFilterInterface $filter, ?string $identifier = null): self
    {
        $id = $identifier ?: $filter->getIdentifier();
        if (!preg_match('/^[A-Za-z0-9_]+$/', $id ?? '')) {
            throw new InvalidArgumentException(sprintf(
                'Filter %s has an invalid identifier. Only alphanumeric characters and underscores allowed.',
                get_class($filter)
            ));
        }

        $this->filters[$id] = $filter;

        return $this;
    }

    /**
     * @param string $identifier
     * @return FieldFilterInterface|null
     */
    public function getFilterByIdentifier(string $identifier): ?FieldFilterInterface
    {
        if (isset($this->filters[$identifier])) {
            return $this->filters[$identifier];
        }

        return null;
    }

    /**
     * @return FieldFilterInterface[]
     */
    public function getAll(): array
    {
        return $this->filters;
    }
}
