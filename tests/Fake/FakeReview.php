<?php


namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;

class FakeReview extends DataObject implements TestOnly
{
    public static $canCreate = true;
    public static $canEdit = true;
    public static $canDelete = true;
    public static $canView = true;

    private static $db = [
        'Content' => 'Varchar',
        'Rating' => 'Int',
    ];

    private static $has_one = [
        'Author' => Member::class,
        'Product' => FakeProduct::class,
    ];

    private static $extensions = [
        Versioned::class,
    ];

    private static $table_name = 'FakeReview_Test';

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
