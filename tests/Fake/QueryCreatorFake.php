<?php

namespace Chillu\GraphQL\Tests\Fake;

use GraphQL\Type\Definition\Type;
use Chillu\GraphQL\QueryCreator;

class QueryCreatorFake extends QueryCreator
{
    public function type()
    {
        return function() {
            return Type::listOf($this->manager->getType('mytype'));
        };
    }
}
