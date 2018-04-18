<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;
use InvalidArgumentException;

/**
 * Scaffolds a GraphQL query field.
 */
abstract class QueryScaffolder extends OperationScaffolder implements ManagerMutatorInterface, ScaffolderInterface
{
    use DataObjectTypeTrait;

    /**
     * @var bool
     */
    protected $isNested = false;

    /**
     * QueryScaffolder constructor.
     *
     * @param string $operationName
     * @param string $typeName
     * @param ResolverInterface|callable|null $resolver
     * @param string $class
     */
    public function __construct($operationName, $typeName = null, $resolver = null, $class = null)
    {
        $this->dataObjectClass = $class;
        parent::__construct($operationName, $typeName, $resolver);

        if (!$this->typeName && !$this->dataObjectClass) {
            throw new InvalidArgumentException(sprintf(
                '%s::__construct() must take a $typeName or $class parameter.',
                __CLASS__
            ));
        }
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $this->extend('onBeforeAddToManager', $manager);
        if (!$this->isNested) {
            $manager->addQuery(function () use ($manager) {
                return $this->scaffold($manager);
            }, $this->getName());
        }
    }

    /**
     * Set to true if this query is a nested field and should not appear in the root query field
     * @param bool $bool
     * @return $this
     */
    public function setNested($bool)
    {
        $this->isNested = (boolean)$bool;

        return $this;
    }

    /**
     * Get the type from Manager
     *
     * @param Manager $manager
     * @return Type
     */
    protected function getType(Manager $manager)
    {
        // If an explicit type name has been provided, use it.
        if ($this->typeName) {
            return $manager->getType($this->typeName);
        }

        // Fall back on a computed type name
        return StaticSchema::inst()->fetchFromManager(
            $this->dataObjectClass,
            $manager
        );
    }
}
