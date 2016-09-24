<?php

namespace Chillu\GraphQL\Tests\Fake;

use Chillu\GraphQL\MutationCreator;

class MutationCreatorFake extends MutationCreator
{
    public function type()
    {
        return function() {
            return $this->manager->getType('mytype');
        };
    }
}
