<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

class C2a extends C2
{
    private static $db = [
        'C2aField' => 'Varchar',
    ];

    private static $table_name = 'C2a_test';
}
