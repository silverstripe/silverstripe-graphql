<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\GraphQL\MutationCreator;

class MutationCreatorFake extends MutationCreator
{
    public function type()
    {
        return function() {
            return $this->manager->getType('mytype');
        };
    }
}
