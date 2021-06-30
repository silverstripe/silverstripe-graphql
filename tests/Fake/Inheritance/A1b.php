<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

class A1b extends A1
{
    private static $db = [
        'A1bField' => 'Varchar',
    ];

    private static $table_name = 'A1b_test';
}
