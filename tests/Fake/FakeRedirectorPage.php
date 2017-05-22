<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;

/**
 * Because otherwise we have to include silverstripe-cms as a dependency just
 * to get the test to work.
 */
class FakeRedirectorPage extends FakePage implements TestOnly
{
    private static $table_name = "GraphQL_FakeRedirectorPage";

    private static $db = [
        'RedirectionType' => "Enum('Internal,External','Internal')",
        'ExternalURL' => 'Varchar(2083)'
    ];
}
