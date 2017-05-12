<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\MutationCreator;

class MutationCreatorFake extends MutationCreator implements TestOnly
{
    public function type()
    {
        return $this->manager->getType('mytype');
    }
}
