<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use Exception;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Filters\FilterInterface;
use SilverStripe\GraphQL\Filters\FilterAware;
use SilverStripe\GraphQL\Filters\ListFilterInterface;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ListQueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Member;
use InvalidArgumentException;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class Read extends ListQueryScaffolder implements OperationResolver, CRUDInterface
{
    use FilterAware;

    /**
     * Read constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, $this, $dataObjectClass);
    }

    /**
     * @param array $args
     * @return DataList
     */
    protected function getResults($args)
    {
        $list = DataList::create($this->getDataObjectClass());
        $registry = $this->getFilterRegistry();
        if (!$registry) {
            return $list;
        }

        if (isset($args['Filter']) && !empty($args['Filter'])) {
            foreach ($this->getFieldFilters($args['Filter']) as $tuple) {
                /* @var FilterInterface $filter */
                list ($filter, $field, $value) = $tuple;
                $list = $filter->applyInclusion($list, $field, $value);
            }
        }
        if (isset($args['Exclude']) && !empty($args['Exclude'])) {
            foreach ($this->getFieldFilters($args['Exclude']) as $tuple) {
                /* @var FilterInterface $filter */
                list ($filter, $field, $value) = $tuple;
                $list = $filter->applyExclusion($list, $field, $value);
            }
        }

        return $list;
    }

    /**
     * @return string
     */
    public function getName()
    {
        $name = parent::getName();
        if ($name) {
            return $name;
        }

        $typePlural = $this->pluralise($this->getTypeName());
        return 'read' . ucfirst($typePlural);
    }

    /**
     * @param Member $member
     * @return boolean
     */
    protected function checkPermission(Member $member = null)
    {
        return $this->getDataObjectInstance()->canView($member);
    }

    /**
     * @param DataObjectInterface $object
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return mixed
     * @throws Exception
     */
    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        if (!$this->checkPermission($context['currentUser'])) {
            throw new Exception(sprintf(
                'Cannot view %s',
                $this->getDataObjectClass()
            ));
        }

        $list = $this->getResults($args);
        $this->extend('updateList', $list, $args, $context, $info);
        return $list;
    }

    /**
     * Pluralise a name
     *
     * @param string $typeName
     * @return string
     */
    protected function pluralise($typeName)
    {
        // Ported from DataObject::plural_name()
        if (preg_match('/[^aeiou]y$/i', $typeName)) {
            $typeName = substr($typeName, 0, -1) . 'ie';
        }
        $typeName .= 's';
        return $typeName;
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        if (!empty($this->filteredFields)) {
            $manager->addType($this->generateInputType($manager, 'Filter'));
            $manager->addType($this->generateInputType($manager, 'Exclude'));
        }

        parent::addToManager($manager);
    }
    /**
     * Use a generated Input type, and require an ID.
     *
     * @param Manager $manager
     * @return array
     */
    protected function createDefaultArgs(Manager $manager)
    {
        if (empty($this->filteredFields)) {
            return [];
        }
        return [
            'Filter' => [
                'type' => $manager->getType($this->inputTypeName('Filter')),
            ],
            'Exclude' => [
                'type' => $manager->getType($this->inputTypeName('Exclude')),
            ],
        ];
    }

    /**
     * @param string $key
     * @return string
     */
    protected function inputTypeName($key = '')
    {
        return $this->getDataObjectTypeName() . $key . 'ReadInputType';
    }

    /**
     * Get a DBField, __ notation allowed.
     * @param string $field
     * @return DBField
     */
    protected function getDBField($field)
    {
        $dbField = null;
        if (stristr($field, FilterInterface::SEPARATOR) !== false) {
            list ($relationName, $relationField) = explode(FilterInterFace::SEPARATOR, $field);
            $class = $this->getDataObjectInstance()->getRelationClass($relationName);
            if (!$class) {
                throw new InvalidArgumentException(sprintf(
                    'Could not find relation %s on %s',
                    $relationName,
                    $this->getDataObjectClass()
                ));
            }
            return Injector::inst()->get($class)->dbObject($relationField);
        }

        return $this->getDataObjectInstance()->dbObject($field);
    }

    /**
     * @param Manager $manager
     * @param string $key
     * @return InputObjectType
     */
    protected function generateInputType(Manager $manager, $key = '')
    {
        $filteredFields = $this->filteredFields;
        return new InputObjectType([
            'name' => $this->inputTypeName($key),
            'fields' => function () use ($manager, $filteredFields) {
                $fields = [];
                foreach ($filteredFields as $fieldName => $filterIDs) {
                    /* @var DBField|TypeCreatorExtension $db */
                    $db = $this->getDBField($fieldName);
                    foreach ($filterIDs as $filterID) {
                        $filter = $this->getFilterRegistry()->getFilterByIdentifier($filterID);
                        if (!$filter) {
                            throw new Exception(sprintf(
                                'Filter %s not found',
                                $filterID
                            ));
                        }
                        $filterType = $db->getGraphQLType($manager);
                        if ($filter instanceof ListFilterInterface) {
                            $filterType = Type::listOf($filterType);
                        }
                        $fields[$fieldName . FilterInterface::SEPARATOR . $filterID] = [
                            'type' => $filterType,
                        ];
                    }
                }
                return $fields;
            }
        ]);
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
        foreach ($dbField->config()->default_filters as $filterID) {
            $this->addFieldFilter($field, $filterID);
        }

        return $this;
    }

    /**
     * Adds all the default filters for every field on the dataobject
     * @return $this
     */
    public function addAllFilters()
    {
        $fields = array_keys(DataObject::getSchema()->databaseFields($this->getDataObjectClass()));
        foreach ($fields as $fieldName) {
            $this->addDefaultFilters($fieldName);
        }

        return $this;
    }

    public function applyConfig(array $config)
    {
        parent::applyConfig($config);
        if (!isset($config['filters'])) {
            return;
        }
        if ($config['filters'] === SchemaScaffolder::ALL) {
            $this->addAllFilters();
        } else if (is_array($config['filters'])) {
            foreach ($config['filters'] as $fieldName => $filterConfig) {
                if ($filterConfig === true) {
                    $this->addDefaultFilters($fieldName);
                } else if (ArrayLib::is_associative($filterConfig)) {
                    foreach ($filterConfig as $filterID => $include) {
                        if (!$include) {
                            continue;
                        }
                        $this->addFieldFilter($fieldName, $filterID);
                    }
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'Filters on field "%s" must be a map of filter ID to a boolean value',
                        $fieldName
                    ));
                }
            }
        } else {
            throw new InvalidArgumentException(sprintf(
                'Config setting "filters" must be an array mapping field names to a list of filter identifiers, or %s for all',
                SchemaScaffolder::ALL
            ));
        }
    }

}
