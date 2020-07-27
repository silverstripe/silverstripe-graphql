<?php


namespace SilverStripe\GraphQL\QueryFilter;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Scaffolding\Traits\Chainable;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\InputType;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBField;
use Generator;

class DataObjectQueryFilter implements ConfigurationApplier
{
    use Chainable;
    use DataObjectTypeTrait;
    use Injectable;

    const SEPARATOR = '__';

    /**
     * @var array
     */
    private static $dependencies = [
        'FieldAccessor' => '%$' . FieldAccessor::class,
    ];

    /**
     * @var FilterRegistryInterface
     */
    private $filterRegistry;

    /**
     * @var array A map of field name to a list of filter identifiers
     */
    private $filteredFields = [];

    /**
     * @var string
     */
    private $fieldName = 'filter';

    /**
     * @var InputType[]
     */
    private $inputTypeCache;

    /**
     * @var FieldAccessor
     */
    private $fieldAccessor;

    /**
     * DataObjectQueryFilter constructor.
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        $this->setDataObjectClass($dataObjectClass);
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->filteredFields);
    }

    /**
     * @param string $fieldName
     * @param string $filterIdentifier
     * @return $this
     */
    public function addFieldFilterByIdentifier(string $fieldName, string $filterIdentifier): DataObjectQueryFilter
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
     * @return $this
     */
    public function addFieldFilter($fieldName, FieldFilterInterface $filter): DataObjectQueryFilter
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
    public function addDefaultFilters(string $field): DataObjectQueryFilter
    {
        $dbField = $this->getDBField($field);
        if (!$dbField) {
            throw new InvalidArgumentException(sprintf(
                'Could not resolve field %s on %s',
                $field,
                $this->getDataObjectClass()
            ));
        }
        foreach ($dbField->config()->get('graphql_default_filters') as $filterID) {
            $this->addFieldFilterByIdentifier($field, $filterID);
        }

        return $this;
    }

    /**
     * Adds all the default filters for every field on the dataobject
     * @return $this
     */
    public function addAllFilters(): DataObjectQueryFilter
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
     * @return InputType
     * @throws SchemaBuilderException
     *
     */
    public function getInputType(string $name, bool $cached = true): InputType
    {
        if ($cached && isset($this->inputTypeCache[$name])) {
            return $this->inputTypeCache[$name];
        }

        $filteredFields = $this->filteredFields;
        $model = DataObjectModel::create($this->dataObjectClass);
        $input = InputType::create($name);
        foreach ($filteredFields as $fieldName => $filterIDs) {
            foreach ($filterIDs as $filterIdOrInstance) {
                $filter = $filterIdOrInstance instanceof FieldFilterInterface
                    ? $filterIdOrInstance
                    : $this->getFilterRegistry()->getFilterByIdentifier($filterIdOrInstance);
                Schema::invariant(
                    $filter,
                    'Filter %s not found',
                    $filterIdOrInstance
                );
                $filterType = $model->getTypeForField($fieldName);
                if (!$filterType) {
                    continue;
                }
                if ($filter instanceof ListFieldFilterInterface) {
                    $filterType = "[{$filterType}]";
                }
                $id = $filterIdOrInstance instanceof FieldFilterInterface
                    ? $filterIdOrInstance->getIdentifier()
                    : $filterIdOrInstance;

                $fieldName .= self::SEPARATOR . $id;
                $input->addField($fieldName, $filterType);
            }
        }

        $this->inputTypeCache[$name] = $input;

        return $this->inputTypeCache[$name];
    }

    /**
     * @param DataList $list
     * @param array $args
     * @return DataList
     */
    public function applyArgsToList(DataList $list, array $args = []): DataList
    {
        $filters = $args[$this->getFieldName()] ?? null;
        if (!$filters) {
            return $list;
        }
        foreach ($this->getFieldFilters($filters) as $tuple) {
            /* @var FieldFilterInterface $filter */
            list ($filter, $field, $value) = $tuple;
            $list = $filter->applyInclusion($list, $field, $value);
        }

        return $list;
    }

    /**
     * @param string $fieldName
     * @return array
     * @throws InvalidArgumentException
     */
    public function getFiltersForField(string $fieldName): array
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
    public function getFilterIdentifiersForField(string $fieldName): array
    {
        return array_keys($this->getFiltersForField($fieldName));
    }


    /**
     * @param string $fieldName
     * @return bool
     */
    public function isFieldFiltered(string $fieldName): bool
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
    public function fieldHasFilter(string $fieldName, string $id): bool
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
    public function removeFieldFilterByIdentifier(string $fieldName, string $id): DataObjectQueryFilter
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
    public function getFieldFilterByIdentifier(string $fieldName, string $id): ?FieldFilterInterface
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
     * @param FilterRegistryInterface $registry
     * @return $this
     */
    public function setFilterRegistry(FilterRegistryInterface $registry): DataObjectQueryFilter
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
     * @return FieldAccessor
     */
    public function getFieldAccessor(): FieldAccessor
    {
        return $this->fieldAccessor;
    }

    /**
     * @param FieldAccessor $fieldAccessor
     * @return DataObjectQueryFilter
     */
    public function setFieldAccessor(FieldAccessor $fieldAccessor): DataObjectQueryFilter
    {
        $this->fieldAccessor = $fieldAccessor;
        return $this;
    }


    /**
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     * @return DataObjectQueryFilter
     */
    public function setFieldName(string $fieldName): DataObjectQueryFilter
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * @param array $filters An array of Field__Filter => Value
     * @return Generator
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
            $filter = $this->getFieldFilterByIdentifier($field, $filterIdentifier);
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
    protected function getDBField($field): DBField
    {
        $dbField = null;
        if (stristr($field, self::SEPARATOR) === false) {
            $normalisedField = $this->getFieldAccessor()->normaliseField($this->getDataObjectInstance(), $field);
            return $this->getDataObjectInstance()->dbObject($normalisedField);
        }

        list ($relationName, $relationField) = explode(self::SEPARATOR, $field);
        $class = $this->getDataObjectInstance()->getRelationClass($relationName);
        if (!$class) {
            throw new InvalidArgumentException(sprintf(
                'Could not find relation %s on %s',
                $relationName,
                $this->getDataObjectClass()
            ));
        }
        $relatedObj = Injector::inst()->get($class);
        $normalisedField = $this->getFieldAccessor()->normaliseField($relatedObj, $relationField);

        return $relatedObj->dbObject($normalisedField);

    }


}
