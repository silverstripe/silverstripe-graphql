<?php

namespace SilverStripe\GraphQL\Scaffolding\Creators;

use SilverStripe\GraphQL\MutationCreator;

/**
 * Creates a GraphQL mutation field
 * @package SilverStripe\GraphQL\Scaffolding\Operations
 */
class MutationOperationCreator extends MutationCreator
{
    use OperationCreatorTrait;

    /**
     * @return \Closure
     */
    public function type()
    {
        return function () {
            return $this->manager->getType($this->typeName);
        };
    }


}