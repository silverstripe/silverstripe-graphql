<?php


namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class FakeProduct extends DataObject implements TestOnly
{
    public static $canCreate = true;
    public static $canEdit = true;
    public static $canDelete = true;
    public static $canView = true;


    private static $db = [
        'Title' => 'Varchar',
        'Price' => 'Int',
    ];

    private static $has_one = [
        'Parent' => FakeProductPage::class,
    ];

    private static $has_many = [
        'Reviews' => FakeReview::class,
    ];

    private static $many_many = [
        'RelatedProducts' => FakeProduct::class,
    ];

    private static $extensions = [
        Versioned::class,
    ];

    private static $owns = [
        'Reviews',
    ];

    private static $table_name = 'FakeProduct_Test';

    /**
     * @return string
     */
    public function getReverseTitle()
    {
        return strrev($this->Title);
    }

    public function Link()
    {
        return 'products/' . $this->ID;
    }

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
