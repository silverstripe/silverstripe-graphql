<?php


namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class FakeProductPage extends DataObject implements TestOnly
{
    public static $canCreate = true;
    public static $canEdit = true;
    public static $canDelete = true;
    public static $canView = true;

    private static $db = [
        'Title' => 'Varchar',
        'BannerContent' => 'Varchar',
    ];

    private static $table_name = 'FakeProductPage_Test';

    private static $has_many = [
        'Products' => FakeProduct::class,
    ];

    private static $owns = [
        'Products',
    ];

    public function canCreate($member = null, $context = [])
    {
        return static::$canCreate;
    }

    public function canEdit($member = null)
    {
        return static::$canEdit;
    }

    public function canDelete($member = null)
    {
        return static::$canDelete;
    }

    public function canView($member = null)
    {
        return static::$canView;
    }
}
