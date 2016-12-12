<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class DataObjectFake extends DataObject implements TestOnly
{
    private static $db = [
        'MyField' => 'Varchar',
        'MyInt' => 'Int'
    ];

    private static $has_one = [
    	'Author' => 'SilverStripe\Security\Member'
    ];

    private static $many_many = [
    	'Files' => 'SilverStripe\Assets\File'
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
