<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;

/**
 * Scaffolds a GraphQL query field.
 */
class ItemQueryScaffolder extends QueryScaffolder
{
    /**
     * @param Manager $manager
     *
     * @return array
     */
    public function scaffold(Manager $manager)
    {
        return [
            'name' => $this->getName(),
            'args' => $this->createArgs($manager),
            'type' => $this->getType($manager),
            'resolve' => $this->createResolverFunction(),
        ];
    }
}
