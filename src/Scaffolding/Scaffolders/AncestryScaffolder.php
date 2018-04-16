<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use InvalidArgumentException;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\ORM\DataObject;

/**
 * Scaffolds a UnionType based on the ancestry of a DataObject class
 * @package SilverStripe\GraphQL\Scaffolding\Scaffolders
 */
class AncestryScaffolder extends UnionScaffolder
{
    /**
     * @var string
     */
    protected $rootClass;

    /**
     * @var string
     */
    protected $suffix;

    /**
     * AncestryScaffolder constructor.
     * @param string $rootDataObjectClass
     * @param string $suffix
     */
    public function __construct($rootDataObjectClass, $suffix = 'WithDescendants')
    {
        if (!class_exists($rootDataObjectClass)) {
            throw new InvalidArgumentException(sprintf(
                'Class %s does not exist.',
                $rootDataObjectClass
            ));
        }

        if (!is_subclass_of($rootDataObjectClass, DataObject::class)) {
            throw new InvalidArgumentException(sprintf(
                'Class %s is not a subclass of %s.',
                $rootDataObjectClass,
                DataObject::class
            ));
        }

        $this->rootClass = $rootDataObjectClass;
        $this->suffix = $suffix;

        parent::__construct(
            $this->generateTypeName(),
            $this->getTypes()
        );
    }

    /**
     * @return string
     */
    public function getRootClass()
    {
        return $this->rootClass;
    }

    /**
     * @param string $rootClass
     * @return AncestryScaffolder
     */
    public function setRootClass($rootClass)
    {
        $this->rootClass = $rootClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * @param string $suffix
     * @return AncestryScaffolder
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * @return array
     */
    public function getAncestry()
    {
        $ancestry = [];
        $currentClass = $this->rootClass;

        while ($currentClass !== DataObject::class) {
            $ancestry[] = $currentClass;
            $currentClass = get_parent_class($currentClass);
        }

        return $ancestry;
    }

    /**
     * Get all the GraphQL types in the ancestry
     * @return array
     */
    public function getTypes()
    {
        return array_map(function ($class) {
            return StaticSchema::inst()->typeNameForDataObject($class);
        }, $this->getAncestry());
    }

    /**
     * @return string
     */
    protected function generateTypeName()
    {
        $prefix = StaticSchema::inst()->typeNameForDataObject($this->rootClass);

        return $prefix . $this->suffix;
    }
}