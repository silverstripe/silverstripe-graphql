<?php

namespace SilverStripe\GraphQL\Filters;

use InvalidArgumentException;

class Registry implements FilterRegistryInterface
{
    /**
     * @var array FilterInterface[]
     */
    protected $filters = [];

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
     * @param FilterInterface $filter
     * @param string|null $identifier
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addFilter(FilterInterface $filter, $identifier = null)
    {
        if (!$filter instanceof FilterInterface) {
            throw new InvalidArgumentException(sprintf(
                '%s filters must be implement the %s interface. See: %s',
                __CLASS__,
                FilterInterface::class,
                get_class($filter)
            ));
        }
        $id = $identifier ?: $filter->getIdentifier();
        if (!preg_match('/^[A-Za-z0-9_]+$/', $id)) {
            throw new InvalidArgumentException(sprintf(
                'Filter %s has an invalid identifier. Only alphanumeric characters and underscores allowed.',
                get_class($filter)
            ));
        }

        $this->filters[$id] = $filter;

        return $this;
    }

    /**
     * @param $identifier
     * @return mixed
     */
    public function getFilterByIdentifier($identifier)
    {
        if (isset($this->filters[$identifier])) {
            return $this->filters[$identifier];
        }

        return null;
    }

    /**
     * @return FilterInterface[]
     */
    public function getAll()
    {
        return $this->filters;
    }
}