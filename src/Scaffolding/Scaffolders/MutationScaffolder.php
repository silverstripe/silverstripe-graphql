<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Creators\MutationOperationCreator;

/**
 * Scaffolds a GraphQL mutation field
 */
class MutationScaffolder extends OperationScaffolder implements ManagerMutatorInterface, ScaffolderInterface
{

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $operationType = $this->getCreator($manager);
        $manager->addMutation(
            $operationType->toArray(),
            $this->getName()
        );
    }

    /**
     * @param Manager $manager
     * @return MutationOperationCreator
     */
    public function getCreator(Manager $manager)
    {
        return new MutationOperationCreator(
            $manager,
            $this->operationName,
            $this->typeName,
            $this->resolver,
            $this->createArgs()
        );
    }

}