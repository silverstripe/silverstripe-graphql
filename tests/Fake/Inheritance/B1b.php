<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

class B1b extends B1
{
    private static $db = [
        'B1bField' => 'Varchar',
    ];

    private static $table_name = 'B1b_test';
}
