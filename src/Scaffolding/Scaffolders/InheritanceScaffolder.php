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

    const MODE_ANCESTRY = 'ancestry';

    const MODE_DESCENDANTS = 'descendants';

    private static $suffix_descendants = 'WithDescendants';

    private static $suffix_ancestors = 'WithAncestors';

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
     * @param string $mode
     * @param string $mode
     */
    public function __construct($rootDataObjectClass, $mode = self::MODE_DESCENDANTS)
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

        $validModes = [self::MODE_ANCESTRY, self::MODE_DESCENDANTS];
        if (!in_array($mode, $validModes)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid mode: %s. Must be one of %s',
                $mode,
                implode(', ', $validModes)
            ));
        }

        $this->mode = $mode;
        $this->rootClass = $rootDataObjectClass;

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
        return $this->mode === self::MODE_DESCENDANTS
            ? $this->config()->suffix_descendants
            : $this->config()->suffix_ancestors;
    }

    /**
     * @return array
     */
    public function getClassTree()
    {
        $schema = StaticSchema::inst();
        $tree = $this->mode === self::MODE_DESCENDANTS
            ? $schema->getDescendants($this->rootClass)
            : $schema->getAncestry($this->rootClass);

        return array_merge([$this->rootClass], $tree);
    }

    /**
     * Get all the GraphQL types in the ancestry
     * @return array
     */
    public function getTypes()
    {
        return array_map(function ($class) {
            return StaticSchema::inst()->typeNameForDataObject($class);
        }, $this->getClassTree());
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

        return $prefix . $this->getSuffix();
    }
}