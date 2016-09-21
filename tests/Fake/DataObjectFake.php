<?php

namespace Chillu\GraphQL\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class DataObjectFake extends DataObject implements TestOnly
{
    public function getCustomGetter()
    {
        return 'customGetterValue';
    }

    public function customMethod()
    {
        return 'customMethodValue';
    }
}
