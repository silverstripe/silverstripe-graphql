<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

class B2 extends B
{
    private static $db = [
        'B2Field' => 'Varchar',
    ];

    private static $table_name = 'B2_test';
}
