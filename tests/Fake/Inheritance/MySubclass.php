<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataList;

class MySubclass extends MyOrig implements TestOnly
{
    private static $db = [
        'MySubclassField' => 'Varchar',
    ];

    private static $table_name = 'MySubclass_test';
}
