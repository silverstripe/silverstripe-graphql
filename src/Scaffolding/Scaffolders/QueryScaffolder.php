<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Creators\QueryOperationCreator;

/**
 * Scaffolds a GraphQL query field
 */
class QueryScaffolder extends OperationScaffolder implements ManagerMutatorInterface, ScaffolderInterface
{

    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager)
    {
        $operationType = $this->getCreator($manager);
        $manager->addQuery(
            $operationType->toArray(),
            $this->getName()
        );
    }

    /**
     * @param Manager $manager
     * @return QueryOperationCreator
     */
    public function getCreator(Manager $manager)
    {
        return new QueryOperationCreator(
            $manager,
            $this->operationName,
            $this->typeName,
            $this->resolver,
            $this->createArgs()
        );
    }
}

