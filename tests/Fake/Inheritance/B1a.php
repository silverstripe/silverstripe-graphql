<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

class B1a extends B1
{
    private static $db = [
        'B1aField' => 'Varchar',
    ];

    private static $table_name = 'B1a_test';
}
