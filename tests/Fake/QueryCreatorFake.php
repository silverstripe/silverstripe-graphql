<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use GraphQL\Type\Definition\Type;
use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\QueryCreator;

class QueryCreatorFake extends QueryCreator implements TestOnly
{
    public function type()
    {
        return Type::listOf($this->manager->getType('mytype'));
    }
}
