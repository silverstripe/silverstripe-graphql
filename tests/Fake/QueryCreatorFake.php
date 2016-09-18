<?php

namespace Chillu\GraphQL\Tests\Fake;

use GraphQL\Type\Definition\Type;
use Chillu\GraphQL\QueryCreator;

class QueryCreatorFake extends QueryCreator
{
    public function type()
    {
        // TODO Avoid type conversion in userland
        return Type::listOf($this->types['mytype']->toType());
    }
}
