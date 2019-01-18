<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\TypeReference;

/**
 * Scaffolds a GraphQL query field.
 */
class ItemQueryScaffolder extends QueryScaffolder
{
    /**
     * @param Manager $manager
     * @return \SilverStripe\GraphQL\Schema\Components\Field
     */
    public function scaffold(Manager $manager)
    {
        return Field::create(
            $this->getName(),
            TypeReference::create($this->getType($manager)->getName()),
            $this->createResolverAbstraction(),
            $this->createArgs($manager)
        );
    }
}
