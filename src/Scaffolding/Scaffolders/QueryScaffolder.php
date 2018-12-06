<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;

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
     * @param OperationResolver|callable|null $resolver
     * @param string $class
     */
    public function __construct($operationName = null, $typeName = null, $resolver = null, $class = null)
    {
        if ($class) {
            $this->setDataObjectClass($class);
        }
        parent::__construct($operationName, $typeName, $resolver);
    }

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        if (!$this->getName() && !$this->dataObjectClass) {
            throw new InvalidArgumentException(sprintf(
                '%s must have either a typeName or dataObjectClass member defined.',
                __CLASS__
            ));
        }

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

    public function getTypeName()
    {
        return parent::getTypeName() ?: $this->typeName();
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
        $typeName = parent::getTypeName();
        if ($typeName && $manager->hasType($typeName)) {
            return $manager->getType($typeName);
        }

        // Fall back on a computed type name
        return StaticSchema::inst()->fetchFromManager(
            $this->dataObjectClass,
            $manager,
            StaticSchema::PREFER_UNION
        );
    }
}
