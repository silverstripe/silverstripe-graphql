<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

class A2 extends A
{
    private static $db = [
        'A2Field' => 'Varchar',
    ];

    private static $table_name = 'A2_test';
}
