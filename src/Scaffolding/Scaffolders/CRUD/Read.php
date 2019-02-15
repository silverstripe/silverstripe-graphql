<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use Exception;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Filters\FilterInterface;
use SilverStripe\GraphQL\Filters\FilterAware;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ListQueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataList;
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

        if (!empty($args['Filter'])) {
            foreach ($this->getFieldFilters($args['Filter']) as $tuple) {
                /* @var FilterInterface $filter */
                list ($filter, $field, $value) = $tuple;
                $list = $filter->applyInclusion($list, $field, $value);
            }
        }
        if (!empty($args['Exclude'])) {
            $map = $this->getFieldFilters($args['Exclude']);
            foreach ($map as $fieldName => $filters) {
                /* @var FilterInterface $filter */
                foreach ($filters as $filter) {
                    $list = $filter->applyExclusion($list, $fieldName);
                }
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
     * @param string $key
     * @return string
     */
    protected function inputTypeName($key = '')
    {
        return $this->getTypeName() . $key . 'InputType';
    }

    /**
     * @param Manager $manager
     * @param string $key
     * @return InputObjectType
     */
    protected function generateInputType(Manager $manager, $key = '')
    {
        return new InputObjectType([
            'name' => $this->inputTypeName($key),
            'fields' => function () use ($manager) {
                $fields = [];
                foreach ($this->filteredFields as $fieldName => $filterIDs) {
                    /* @var DBField|TypeCreatorExtension $db */
                    $db = $this->getDataObjectInstance()->dbObject($fieldName);

                    foreach ($filterIDs as $filterID) {
                        $fields[$fieldName . '__' . $filterID] = [
                            'type' => $db->getGraphQLType($manager),
                        ];
                    }
                }
                return $fields;
            }
        ]);
    }

    public function applyConfig(array $config)
    {
        parent::applyConfig($config);
        if (isset($config['filters'])) {
            if (!is_array($config['filters'])) {
                throw new InvalidArgumentException(
                    'Config setting "filters" must be an array mapping field names to a list of filter idenfifiers'
                );
            }

            foreach ($config['filters'] as $fieldName => $filterConfig) {
                if ($filterConfig === SchemaScaffolder::ALL) {
                    $filters = array_keys($this->getFilterRegistry()->getAll());
                } else if (ArrayLib::is_associative($filterConfig)) {
                    $filters = [];
                    foreach ($filterConfig as $filterID => $include) {
                        if (!$include) {
                            continue;
                        }
                        $filters[] = $filterID;
                    }
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'Filters on field "%s" must be a map of filter ID to a boolean value'
                    ));
                }

                foreach ($filters as $filterID) {
                    $this->addFieldFilter($fieldName, $filterID);
                }
            }
        }
    }

}
