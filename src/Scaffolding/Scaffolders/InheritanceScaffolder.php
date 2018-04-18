<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use InvalidArgumentException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\ORM\DataObject;

/**
 * Scaffolds a UnionType based on the ancestry of a DataObject class
 * @package SilverStripe\GraphQL\Scaffolding\Scaffolders
 */
class InheritanceScaffolder extends UnionScaffolder implements ManagerMutatorInterface
{
    use Configurable;

    /**
     * @var string
     */
    protected $rootClass;

    /**
     * @var string
     */
    protected $suffix;

    /**
     * @var string
     */
    private $mode;

    /**
     * AncestryScaffolder constructor.
     * @param string $rootDataObjectClass
     * @param string $suffix
     */
    public function __construct($rootDataObjectClass, $suffix = '')
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
     * @return InheritanceScaffolder
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
     * @param $suffix
     * @return $this
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Get all the GraphQL types in the ancestry
     * @return array
     */
    public function getTypes()
    {
        $schema = StaticSchema::inst();
        $tree = array_merge(
            [$this->rootClass],
            $schema->getDescendants($this->rootClass)
        );

        return array_map(function ($class) use ($tree, $schema) {
            return $schema->typeNameForDataObject($class);
        }, $tree);
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $types = $this->getTypes();
        if (sizeof($types) === 1) {
            return;
        }

        $manager->addType(
            $this->scaffold($manager),
            $this->getName()
        );
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