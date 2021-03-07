<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;


use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class A extends DataObject implements TestOnly
{
    private static $db = [
        'AField' => 'Varchar',
    ];

    private static $table_name = 'A_test';
}
