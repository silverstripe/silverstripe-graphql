<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

class C2 extends C
{
    private static $db = [
        'C2Field' => 'Varchar',
    ];

    private static $table_name = 'C2_test';
}
