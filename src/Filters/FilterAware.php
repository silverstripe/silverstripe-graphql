<?php


namespace SilverStripe\GraphQL\Filters;

use InvalidArgumentException;

trait FilterAware
{

    /**
     * @var FilterRegistryInterface
     */
    protected $filterRegistry;

    /**
     * @var array A map of field name to a list of filter identifiers
     */
    protected $filteredFields = [];

    /**
     * @param FilterRegistryInterface $registry
     * @return $this
     */
    public function setFilterRegistry(FilterRegistryInterface $registry)
    {
        $this->filterRegistry = $registry;

        return $this;
    }

    /**
     * @return FilterRegistryInterface
     */
    public function getFilterRegistry()
    {
        return $this->filterRegistry;
    }

    /**
     * @param $fieldName
     * @param $filterIdentifier
     * @return $this
     */
    public function addFieldFilter($fieldName, $filterIdentifier)
    {
        if (!isset($this->filteredFields[$fieldName])) {
            $this->filteredFields[$fieldName] = [];
        }

        $this->filteredFields[$fieldName][$filterIdentifier] = $filterIdentifier;

        return $this;
    }

    /**
     * @param array $filters An array of Field__Filter => Value
     * @return \Generator
     */
    protected function getFieldFilters(array $filters)
    {
        foreach ($filters as $key => $val) {
            $pos = strrpos($key, FilterInterface::SEPARATOR);
            // falsy is okay here because a leading __ is invalid.
            if(!$pos) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid filter %s. Must be a composite string of field name, filter identifier, separated by %s',
                    $key,
                    FilterInterface::SEPARATOR
                ));
            }
            $parts = explode(FilterInterface::SEPARATOR, $key);
            $filterIdentifier = array_pop($parts);
            // If the field segment contained __, that implies relationship (dot notation)
            $field = implode('.', $parts);
            if (!isset($result[$field])) {
                $result[$field] = [];
            }
            $filter = $this->getFilterRegistry()->getFilterByIdentifier($filterIdentifier);
            if (!$filter) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid filter "%s".',
                    $filterIdentifier
                ));
            }

            yield [$filter, $field, $val];
        }
    }

}
