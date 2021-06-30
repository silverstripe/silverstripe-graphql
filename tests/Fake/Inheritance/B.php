<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class B extends DataObject implements TestOnly
{
    private static $db = [
        'BField' => 'Varchar',
    ];

    private static $table_name = 'B_test';
}
