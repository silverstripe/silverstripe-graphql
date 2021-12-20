<?php

namespace SilverStripe\GraphQL\Tests\Fake\SubFake;

use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;

class FakePage extends FakeSiteTree
{
    private static $table_name = "GraphQL_SubFakePage";

    private static $db = [
        'SubFakePageField' => 'Varchar',
    ];
}
