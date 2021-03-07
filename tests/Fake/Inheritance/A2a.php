<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

class A2a extends A2
{
    private static $db = [
        'A2aField' => 'Varchar',
    ];

    private static $table_name = 'A2a_test';
}
