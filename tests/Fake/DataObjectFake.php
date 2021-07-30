<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Assets\File;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Member;

/**
 * @property string $MyField
 * @property int $MyInt
 * @method Member Author()
 * @method ManyManyList Files()
 */
class DataObjectFake extends DataObject implements TestOnly
{
    private static $table_name = 'GraphQL_DataObjectFake';

    private static $db = [
        'MyField' => 'Varchar',
        'MyInt' => 'Int',
        'MyDate' => 'Datetime',
        'MyCurrency' => 'Currency',
        'MyText' => 'Text',
        'MyEnum' => "Enum('ONE, TWO')",
        'MyMoney' => 'Money',
    ];

    private static $has_one = [
        'Author' => Member::class
    ];

    private static $many_many = [
        'Files' => File::class
    ];

    private static $searchable_fields = [
        'MyField',
        'MyInt',
    ];

    private static $default_sort = '"GraphQL_DataObjectFake"."MyField" ASC';

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

    public function canCreate($member = null, $context = [])
    {
        return true;
    }

    public function canEdit($member = null)
    {
        return true;
    }

    public function canView($member = null)
    {
        return true;
    }

    public function canDelete($member = null)
    {
        return true;
    }
}
