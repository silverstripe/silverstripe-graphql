<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use Exception;

/**
 * Scaffolds a GraphQL mutation field.
 */
class MutationScaffolder extends OperationScaffolder implements ManagerMutatorInterface, ScaffolderInterface
{
    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $this->extend('onBeforeAddToManager', $this, $manager);

        if (!$this->operationName) {
            throw new Exception(sprintf(
                '%s Tried to add to manager before an operation name was assigned. Did you forget to call setName()?',
                __CLASS__
            ));
        }

        $manager->addMutation(function () use ($manager) {
            return $this->scaffold($manager);
        }, $this->getName());
    }

    /**
     * @param Manager $manager
     *
     * @return array
     */
    public function scaffold(Manager $manager)
    {
        $typeName = $this->typeName
            ?: StaticSchema::inst()->typeNameForDataObject($this->dataObjectClass);

        return [
            'name' => $this->operationName,
            'args' => $this->createArgs($manager),
            'type' => $manager->getType($typeName),
            'resolve' => $this->createResolverFunction(),
        ];
    }
}
