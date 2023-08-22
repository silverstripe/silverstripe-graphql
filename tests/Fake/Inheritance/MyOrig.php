<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

class MyOrig extends DataObject implements TestOnly
{
    private static $db = [
        'MyField' => 'Varchar',
    ];

    private static $table_name = 'MyOrig_test';
}
