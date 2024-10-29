<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Mode\Versioned;

class FakeSiteTree extends DataObject implements TestOnly
{
    private static $table_name = "GraphQL_FakeSiteTree";

    private static $db = [
        'Title' => 'Varchar',
        'Content' => 'HTMLText'
    ];

    private static $has_one = [
        'Parent' => FakeSiteTree::class,
    ];

    private static $has_many = [
        'Children' => FakeSiteTree::class,
    ];

    private static $extensions = [
        Versioned::class,
    ];

    public function canView($member = null)
    {
        return true;
    }
}
