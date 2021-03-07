<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class C extends DataObject implements TestOnly
{
    private static $db = [
        'CField' => 'Varchar',
    ];

    private static $table_name = 'C_test';
}
