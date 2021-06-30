<?php


namespace SilverStripe\GraphQL\Tests\Fake\Inheritance;

class C1 extends C
{
    private static $db = [
        'C1Field' => 'Varchar',
    ];

    private static $table_name = 'C1_test';
}
