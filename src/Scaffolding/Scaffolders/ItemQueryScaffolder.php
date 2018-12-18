<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use GraphQL\Error\Error;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Serialisation\SerialisableFieldDefinition;

/**
 * Scaffolds a GraphQL query field.
 */
class ItemQueryScaffolder extends QueryScaffolder
{
    /**
     * @param Manager $manager
     * @throws Error
     * @return SerialisableFieldDefinition
     */
    public function scaffold(Manager $manager)
    {
        return SerialisableFieldDefinition::create([
            'name' => $this->getName(),
            'args' => $this->createArgs($manager),
            'type' => $this->getType($manager),
            'resolve' => $this->createResolverFunction(),
            'resolverFactory' => $this->resolverFactory,
        ]);
    }
}
