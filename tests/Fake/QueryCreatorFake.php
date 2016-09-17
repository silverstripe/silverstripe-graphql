<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\QueryCreator;

class QueryCreatorFake extends QueryCreator
{
    public function type()
    {
        // TODO Avoid type conversion in userland
        return Type::listOf($this->types['mytype']->toType());
    }
}
