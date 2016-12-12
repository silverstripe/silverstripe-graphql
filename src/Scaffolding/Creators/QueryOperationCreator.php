<?php

namespace SilverStripe\GraphQL\Scaffolding\Creators;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\QueryCreator;
use GraphQL\Type\Definition\Type;

/**
 * Creates a GraphQL query field
 * @package SilverStripe\GraphQL\Scaffolding\Operations
 */
class QueryOperationCreator extends QueryCreator
{

    use OperationCreatorTrait;

    /**
     * @return \Closure
     */
    public function type()
    {
        return function () {
            return Type::listOf($this->manager->getType(
                $this->typeName
            ));
        };
    }

}
