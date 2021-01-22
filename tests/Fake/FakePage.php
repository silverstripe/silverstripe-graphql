<?php

namespace SilverStripe\GraphQL\Tests\Fake;

class FakePage extends FakeSiteTree
{
    private static $table_name = "GraphQL_FakePage";

    private static $db = [
        'FakePageField' => 'Varchar',
    ];
}
