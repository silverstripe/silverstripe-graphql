<?php

namespace SilverStripe\GraphQL\Scaffolding;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Config;

/**
 * Offers a few helper methods for classes that are DataObject subclass bound.
 */
trait DataObjectTypeTrait
{
    /**
     * @var string
     */
    protected $dataObjectClass;

    /**
     * @var string
     */
    protected $dataObjectTypeName;

    /**
     * @var DataObject
     */
    protected $dataObjectInstance;

    /**
     * @return string
     */
    public function getDataObjectClass()
    {
        return $this->dataObjectClass;
    }

    /**
     * @return string
     */
    public function typeName()
    {
        return $this->typeNameForDataObject($this->dataObjectClass);
    }

    /**
     * @return mixed
     */
    public function getDataObjectInstance()
    {
        if ($this->dataObjectInstance) {
            return $this->dataObjectInstance;
        }

        return $this->dataObjectInstance = Injector::inst()->get($this->dataObjectClass);
    }

    /**
     * Sets the DataObject name
     * @param string $name
     * @return  $this
     */
    public function setDataObjectClass($class)
    {
        $this->dataObjectClass = $class;

        return $this;
    }

    /**
     * Given a DataObject subclass name, transform it into a sanitised (and implicitly unique) type
     * name suitable for the GraphQL schema
     * 
     * @param $class
     * @return mixed
     */
    protected function typeNameForDataObject($class)
    {
        $typeName = Config::inst()->get($class, 'table_name', Config::UNINHERITED) ?:
            Injector::inst()->get($class)->singular_name();

        return preg_replace('/[^A-Za-z0-9_]/', '_', $typeName);
    }

}