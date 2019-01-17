<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\TypeAbstractions\FieldAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\TypeReference;

/**
 * Scaffolds a GraphQL query field.
 */
class ItemQueryScaffolder extends QueryScaffolder
{
    /**
     * @param Manager $manager
     * @return FieldAbstraction
     */
    public function scaffold(Manager $manager)
    {
        return FieldAbstraction::create(
            $this->getName(),
            TypeReference::create($this->getType($manager)->getName()),
            $this->createResolverAbstraction(),
            $this->createArgs($manager)
        );
    }
}
