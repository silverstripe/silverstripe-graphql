<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\LazyTypeReference;

/**
 * Scaffolds a GraphQL query field.
 */
class ItemQueryScaffolder extends QueryScaffolder
{
    /**
     * @param Manager $manager
     * @return Field
     */
    public function scaffold(Manager $manager)
    {
        return Field::create(
            $this->getName(),
            LazyTypeReference::create(function () use ($manager) {
                return $this->getType($manager);
            }),
            $this->createResolverAbstraction(),
            $this->createArgs($manager)
        );
    }
}
