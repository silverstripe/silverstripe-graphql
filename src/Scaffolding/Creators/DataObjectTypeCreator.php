<?php

namespace SilverStripe\GraphQL\Scaffolding\Creators;

use SilverStripe\GraphQL\TypeCreator;
use SilverStripe\GraphQL\Manager;
use SilverStripe\ORM\DataObjectInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Creates a GraphQL type for a DataObject
 */
class DataObjectTypeCreator extends TypeCreator
{

    /**
     * @var string
     */
    protected $typeName;


    /**
     * @var array
     */
    protected $fieldsMap;


    /**
     * DataObjectTypeCreator constructor.
     * @param Manager $manager
     * @param string $typeName
     * @param array $fields
     */
    public function __construct(Manager $manager, $typeName, $fields)
    {
        $this->typeName = $typeName;
        $this->fieldsMap = $fields;

        parent::__construct($manager);
    }


    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => $this->typeName
        ];
    }


    /**
     * @return array
     */
    public function fields()
    {
        return $this->fieldsMap;
    }


    /**
     * @param DataObjectInterface $obj
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolveField(DataObjectInterface $obj, array $args, $context, ResolveInfo $info)
    {
        return $obj->obj($info->fieldName);
    }
}