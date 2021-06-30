<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

class A1a extends A1
{
    private static $db = [
        'A1aField' => 'Varchar',
    ];

    private static $table_name = 'A1a_test';
}
