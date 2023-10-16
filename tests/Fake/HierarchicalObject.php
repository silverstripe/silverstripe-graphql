<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class HierarchicalObject extends DataObject implements TestOnly
{
    private static $table_name = "GraphQL_HierarchicalObject";

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'Parent' => self::class,
    ];

    private static $has_many = [
        'Children' => self::class,
    ];

    public function canView($member = null)
    {
        return true;
    }
}
