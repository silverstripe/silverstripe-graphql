<?php

namespace SilverStripe\GraphQL\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class DataObjectFake extends DataObject implements TestOnly
{
    private static $db = [
        'MyField' => 'Varchar'
    ];

    public $customSetterFieldResult;

    public $customSetterMethodResult;

    public function getCustomGetter()
    {
        return 'customGetterValue';
    }

    public function customMethod()
    {
        return 'customMethodValue';
    }

    public function setCustomSetterField($val)
    {
        $this->customSetterFieldResult = $val;
    }

    public function customSetterMethod($val)
    {
        $this->customSetterMethodResult = $val;
    }
}
