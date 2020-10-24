<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\QueryFilter\DataObjectQueryFilter;
use SilverStripe\GraphQL\QueryFilter\QueryFilterAware;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ListQueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Security\Member;
use InvalidArgumentException;

/**
 * Scaffolds a generic read operation for DataObjects.
 */
class Read extends ListQueryScaffolder implements OperationResolver, CRUDInterface
{
    use QueryFilterAware;

    const FILTER = 'Filter';

    const EXCLUDE = 'Exclude';

    /**
     * Read constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, $this, $dataObjectClass);
        $filter = Injector::inst()->create(DataObjectQueryFilter::class, $dataObjectClass)
            ->setFilterKey(StaticSchema::inst()->formatField(self::FILTER))
            ->setExcludeKey(StaticSchema::inst()->formatField(self::EXCLUDE));
        $this->setQueryFilter($filter);
    }

    /**
     * @param array $args
     * @return DataList
     */
    protected function getResults($args)
    {
        $list = DataList::create($this->getDataObjectClass());
        if (!$this->queryFilter->exists()) {
            return $list;
        }
        return $this->queryFilter->applyArgsToList($list, $args);
    }

    /**
     * @param DataObjectQueryFilter $filter
     * @return $this
     */
    public function setQueryFilter(DataObjectQueryFilter $filter)
    {
        $this->queryFilter = $filter;

        return $this;
    }

    /**
     * A "find or make" API useful for the fluent declarations in scaffolding code.
     * @return DataObjectQueryFilter
     */
    public function queryFilter()
    {
        return $this->queryFilter;
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
     * @param DataObjectInterface $object
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return mixed
     * @throws Exception
     */
    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
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
     * Use a generated Input type, and require an ID.
     *
     * @param Manager $manager
     * @return array
     */
    protected function createDefaultArgs(Manager $manager)
    {
        if (!$this->queryFilter->exists()) {
            return [];
        }
        $filterKey = StaticSchema::inst()->formatField(self::FILTER);
        $excludeKey = StaticSchema::inst()->formatField(self::EXCLUDE);

        return [
            $filterKey => [
                'type' => $manager->getType($this->inputTypeName(self::FILTER)),
            ],
            $excludeKey => [
                'type' => $manager->getType($this->inputTypeName(self::EXCLUDE)),
            ],
        ];
    }

    /**
     * @param string $key
     * @return string
     */
    protected function inputTypeName($key = '')
    {
        return $this->getTypeName() . $key . 'ReadInputType';
    }


    public function applyConfig(array $config)
    {
        parent::applyConfig($config);

        if (isset($config['filters'])) {
            if ($config['filters'] === SchemaScaffolder::ALL) {
                $this->queryFilter->addAllFilters();
            } else {
                if (is_array($config['filters'])) {
                    $this->queryFilter->applyConfig($config['filters']);
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'Config setting "filters" must be an array mapping field names to a list of filter identifiers, or %s for all',
                        SchemaScaffolder::ALL
                    ));
                }
            }
        }
    }
}
