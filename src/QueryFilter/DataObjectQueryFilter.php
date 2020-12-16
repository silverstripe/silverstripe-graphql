<?php


namespace SilverStripe\GraphQL\QueryFilter;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Scaffolding\Traits\Chainable;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBField;
use Exception;

class DataObjectQueryFilter implements ConfigurationApplier
{
    use Chainable;
    use DataObjectTypeTrait;
    use Injectable;

    const SEPARATOR = '__';

    /**
     * @var FilterRegistryInterface
     */
    protected $filterRegistry;

    /**
     * @var array A map of field name to a list of filter identifiers
     */
    protected $filteredFields = [];

    /**
     * @var string
     */
    protected $filterKey = 'Filter';

    /**
     * @var string
     */
    protected $excludeKey = 'Exclude';

    /**
     * @var InputObjectType[]
     */
    protected $inputTypeCache;

    /**
     * DataObjectQueryFilter constructor.
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->setDataObjectClass($dataObjectClass);
    }

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
     * @return string
     */
    public function getFilterKey()
    {
        return $this->filterKey;
    }

    /**
     * @param string $filterKey
     * @return DataObjectQueryFilter
     */
    public function setFilterKey($filterKey)
    {
        $this->filterKey = $filterKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getExcludeKey()
    {
        return $this->excludeKey;
    }

    /**
     * @param string $excludeKey
     * @return DataObjectQueryFilter
     */
    public function setExcludeKey($excludeKey)
    {
        $this->excludeKey = $excludeKey;
        return $this;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return !empty($this->filteredFields);
    }

    /**
     * @param $fieldName
     * @param $filterIdentifier
     * @return $this
     */
    public function addFieldFilterByIdentifier($fieldName, $filterIdentifier)
    {
        if (!isset($this->filteredFields[$fieldName])) {
            $this->filteredFields[$fieldName] = [];
        }

        $this->filteredFields[$fieldName][$filterIdentifier] = $filterIdentifier;

        return $this;
    }

    /**
     * @param $fieldName
     * @param FieldFilterInterface $filter
     */
    public function addFieldFilter($fieldName, FieldFilterInterface $filter)
    {
        if (!isset($this->filteredFields[$fieldName])) {
            $this->filteredFields[$fieldName] = [];
        }

        $this->filteredFields[$fieldName][$filter->getIdentifier()] = $filter;

        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function addDefaultFilters($field)
    {
        $dbField = $this->getDBField($field);
        if (!$dbField) {
            throw new InvalidArgumentException(sprintf(
                'Could not resolve field %s on %s',
                $field,
                $this->getDataObjectClass()
            ));
        }
        foreach ($dbField->config()->graphql_default_filters as $filterID) {
            $this->addFieldFilterByIdentifier($field, $filterID);
        }

        return $this;
    }

    /**
     * Adds all the default filters for every field on the dataobject
     * @return $this
     */
    public function addAllFilters()
    {
        $fields = array_keys($this->getDataObjectInstance()->searchableFields());
        foreach ($fields as $fieldName) {
            $this->addDefaultFilters($fieldName);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param bool $cached
     * @return InputObjectType
     */
    public function getInputType($name, $cached = true)
    {
        if ($cached && isset($this->inputTypeCache[$name])) {
            return $this->inputTypeCache[$name];
        }

        $filteredFields = $this->filteredFields;
        $input = new InputObjectType([
            'name' => $name,
            'fields' => function () use ($filteredFields) {
                $fields = [];
                foreach ($filteredFields as $fieldName => $filterIDs) {
                    /* @var DBField|TypeCreatorExtension $db */
                    $db = $this->getDBField($fieldName);
                    foreach ($filterIDs as $filterIdOrInstance) {
                        $filter = $filterIdOrInstance instanceof FieldFilterInterface
                            ? $filterIdOrInstance
                            : $this->getFilterRegistry()->getFilterByIdentifier($filterIdOrInstance);

                        if (!$filter) {
                            throw new Exception(sprintf(
                                'Filter %s not found',
                                $filterIdOrInstance
                            ));
                        }
                        $filterType = $db->getGraphQLType();
                        if ($filter instanceof ListFieldFilterInterface) {
                            $filterType = Type::listOf($filterType);
                        }
                        $id = $filterIdOrInstance instanceof FieldFilterInterface
                            ? $filterIdOrInstance->getIdentifier()
                            : $filterIdOrInstance;

                         $fields[$fieldName . self::SEPARATOR . $id] = [
                            'type' => $filterType,
                         ];
                    }
                }
                return $fields;
            }
        ]);

        $this->inputTypeCache[$name] = $input;

        return $this->inputTypeCache[$name];
    }

    /**
     * @param DataList $list
     * @param array $args
     * @return DataList
     */
    public function applyArgsToList(DataList $list, $args = [])
    {
        if (isset($args[$this->getFilterKey()]) && !empty($args[$this->getFilterKey()])) {
            foreach ($this->getFieldFilters($args[$this->getFilterKey()]) as $tuple) {
                /* @var FieldFilterInterface $filter */
                list ($filter, $field, $value) = $tuple;
                $list = $filter->applyInclusion($list, $field, $value);
            }
        }
        if (isset($args[$this->getExcludeKey()]) && !empty($args[$this->getExcludeKey()])) {
            foreach ($this->getFieldFilters($args[$this->getExcludeKey()]) as $tuple) {
                /* @var FieldFilterInterface $filter */
                list ($filter, $field, $value) = $tuple;
                $list = $filter->applyExclusion($list, $field, $value);
            }
        }

        return $list;
    }

    /**
     * @param string $fieldName
     * @return array
     * @throws InvalidArgumentException
     */
    public function getFiltersForField($fieldName)
    {
        if (isset($this->filteredFields[$fieldName])) {
            return $this->filteredFields[$fieldName];
        }

        throw new InvalidArgumentException(sprintf(
            'Field %s not found',
            $fieldName
        ));
    }

    /**
     * @param string $fieldName
     * @return array
     * @throws InvalidArgumentException
     */
    public function getFilterIdentifiersForField($fieldName)
    {
        return array_keys($this->getFiltersForField($fieldName));
    }


    /**
     * @param string $fieldName
     * @return bool
     */
    public function isFieldFiltered($fieldName)
    {
        try {
            $filters = $this->getFiltersForField($fieldName);

            return !empty($filters);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @param string $fieldName
     * @param string $id
     * @return bool
     */
    public function fieldHasFilter($fieldName, $id)
    {
        if ($this->isFieldFiltered($fieldName)) {
            return in_array($id, $this->getFilterIdentifiersForField($fieldName));
        }

        return false;
    }

    /**
     * @param string $fieldName
     * @param string $id
     * @return $this
     */
    public function removeFieldFilterByIdentifier($fieldName, $id)
    {
        if ($this->isFieldFiltered($fieldName)) {
            unset($this->filteredFields[$fieldName][$id]);
        }

        return $this;
    }

    /**
     * @param $fieldName
     * @param $id
     * @return FieldFilterInterface|null
     */
    public function getFieldFilterByIdentifier($fieldName, $id)
    {
        $filters = $this->getFiltersForField($fieldName);

        return isset($filters[$id]) ? $filters[$id] : null;
    }

    /**
     * @param array $config
     */
    public function applyConfig(array $config)
    {
        foreach ($config as $fieldName => $filterConfig) {
            if ($filterConfig === true) {
                $this->addDefaultFilters($fieldName);
            } elseif (ArrayLib::is_associative($filterConfig)) {
                foreach ($filterConfig as $filterID => $include) {
                    if (!$include) {
                        continue;
                    }
                    $this->addFieldFilterByIdentifier($fieldName, $filterID);
                }
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Filters on field "%s" must be a map of filter ID to a boolean value',
                    $fieldName
                ));
            }
        }
    }

    /**
     * @param array $filters An array of Field__Filter => Value
     * @return \Generator
     */
    protected function getFieldFilters(array $filters)
    {
        foreach ($filters as $key => $val) {
            $pos = strrpos($key, self::SEPARATOR);
            // falsy is okay here because a leading __ is invalid.
            if (!$pos) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid filter %s. Must be a composite string of field name, filter identifier, separated by %s',
                    $key,
                    self::SEPARATOR
                ));
            }
            $parts = explode(self::SEPARATOR, $key);
            $filterIdentifier = array_pop($parts);
            // If the field segment contained __, that implies relationship (dot notation)
            $field = implode('.', $parts);
            // The Field key is written with self::SEPARATOR
            $fieldName = implode(self::SEPARATOR, $parts);
            $filter = $this->getFieldFilterByIdentifier($fieldName, $filterIdentifier);
            if (!$filter instanceof FieldFilterInterface) {
                $filter = $this->getFilterRegistry()->getFilterByIdentifier($filterIdentifier);
            }
            if (!$filter) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid filter "%s".',
                    $filterIdentifier
                ));
            }

            yield [$filter, $field, $val];
        }
    }

    /**
     * Get a DBField, __ notation allowed.
     * @param string $field
     * @return DBField
     */
    protected function getDBField($field)
    {
        $dbField = null;
        if (stristr($field, self::SEPARATOR) !== false) {
            $relationNames = explode(self::SEPARATOR, $field);
            $relationField = array_pop($relationNames);
            // reverse array so we can use the faster array_pop
            $relationNames = array_reverse($relationNames);
            // initialize current class
            $class = get_class($this->getDataObjectInstance());
            do {
                $relationName = array_pop($relationNames);
                $lastClass = $class;
                $class = Injector::inst()->get($class)->getRelationClass($relationName);
            } while ($class && !empty($relationNames));

            if (!$class) {
                throw new InvalidArgumentException(sprintf(
                    'Could not find relation %s on %s for the filter %s',
                    $relationName,
                    $lastClass,
                    $field
                ));
            }
            return Injector::inst()->get($class)->dbObject($relationField);
        }

        return $this->getDataObjectInstance()->dbObject($field);
    }
}
