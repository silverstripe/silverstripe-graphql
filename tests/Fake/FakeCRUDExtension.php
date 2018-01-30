<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Core\Extension;

class FakeCRUDExtension extends Extension
{
    public function augmentMutation()
    {
        return false;
    }
}
