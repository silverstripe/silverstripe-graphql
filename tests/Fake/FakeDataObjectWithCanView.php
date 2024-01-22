<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class FakeDataObjectWithCanView extends DataObject implements TestOnly
{
    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $table_name = 'FakeDataObjectWithCanView_Test';

    public function canView($member = null)
    {
        return $this->ID % 2;
    }
}
