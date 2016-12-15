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
    protected $dataObjectName;

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
    public function getDataObjectName()
    {
        return $this->dataObjectName;
    }

    /**
     * @return string
     */
    public function typeName()
    {
        /* Type names must be unique and predictable. Cannot allow customisation. */
        //return $this->dataObjectTypeName ?: $this->getDataObjectInstance()->singular_name();

        return $this->typeNameForDataObject($this->dataObjectName);
    }

    /**
     * @return mixed
     */
    public function getDataObjectInstance()
    {
        if ($this->dataObjectInstance) {
            return $this->dataObjectInstance;
        }

        return $this->dataObjectInstance = Injector::inst()->get($this->dataObjectName);
    }

    /**
     * Sets the DataObject name
     * @param string $name
     * @return  $this
     */
    public function setDataObjectName($name)
    {
        $this->dataObjectName = $name;

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