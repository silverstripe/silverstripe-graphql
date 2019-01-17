<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\TypeAbstractions\FieldAbstraction;

/**
 * Scaffolds a GraphQL query field.
 */
class ItemQueryScaffolder extends QueryScaffolder
{
    /**
     * @param Manager $manager
     * @return array
     */
    public function scaffold(Manager $manager)
    {
        return new FieldAbstraction(
            $this->getName(),
            $this->getType($manager),
            $this->createResolverAbstraction(),
            $this->createArgs($manager)
        );
    }
}
