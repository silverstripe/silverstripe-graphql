<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

class A1 extends A
{
    private static $db = [
        'A1Field' => 'Varchar',
    ];

    private static $table_name = 'A1_test';
}
