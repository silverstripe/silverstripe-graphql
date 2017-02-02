<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ManagerMutatorInterface;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;

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
        $manager->addMutation(
            $this->scaffold($manager),
            $this->getName()
        );
    }

    /**
     * @param Manager $manager
     *
     * @return array
     */
    public function scaffold(Manager $manager)
    {
        return [
            'name' => $this->operationName,
            'args' => $this->createArgs(),
            'type' => function () use ($manager) {
                return $manager->getType($this->typeName);
            },
            'resolve' => $this->createResolverFunction(),
        ];
    }
}
