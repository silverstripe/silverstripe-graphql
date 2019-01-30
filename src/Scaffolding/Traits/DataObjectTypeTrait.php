<?php

namespace SilverStripe\GraphQL\Scaffolding\Traits;

use BadMethodCallException;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\ORM\DataObject;

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
     * Type name inferred from the dataobject.
     *
     * @return string
     */
    public function getDataObjectTypeName()
    {
        $dataObjectClass = $this->getDataObjectClass();
        if (!$dataObjectClass) {
            throw new BadMethodCallException(__CLASS__ . " must have a dataobject class specified");
        }
        return StaticSchema::inst()->typeNameForDataObject($dataObjectClass);
    }

    /**
     * @return DataObject
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
     * @param string $class
     * @return $this
     */
    public function setDataObjectClass($class)
    {
        if (!$class) {
            throw new InvalidArgumentException("Missing class provided");
        }

        if (!class_exists($class)) {
            throw new InvalidArgumentException("Non-existent classname \"{$class}\"");
        }

        if (!is_subclass_of($class, DataObject::class)) {
            throw new InvalidArgumentException("\"{$class}\" is not a DataObject subclass");
        }

        $this->dataObjectClass = $class;
        return $this;
    }
}
