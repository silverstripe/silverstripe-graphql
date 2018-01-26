<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Core\Extensible;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use SilverStripe\GraphQL\Scaffolding\Traits\DataObjectTypeTrait;

/**
 * Scaffolds a GraphQL query field.
 */
abstract class QueryScaffolder extends OperationScaffolder implements ManagerMutatorInterface, ScaffolderInterface
{
    use DataObjectTypeTrait;
    use Extensible;

    /**
     * @var bool
     */
    protected $isNested = false;

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
     * @param $bool
     * @return $this
     */
    public function setNested($bool)
    {
        $this->isNested = (boolean)$bool;

        return $this;
    }

    /**
     * Creates a thunk that lazily fetches the type
     *
     * @param Manager $manager
     * @return ObjectType
     */
    protected function getType(Manager $manager)
    {
        /** @var ObjectType $type */
        $type = $manager->getType($this->typeName);
        return $type;
    }

}
