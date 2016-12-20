<?php

namespace SilverStripe\GraphQL\Scaffolding;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\Scaffolding\Util\ScaffoldingUtil;

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
        return ScaffoldingUtil::typeNameForDataObject($this->dataObjectClass);
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

}