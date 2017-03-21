<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\ORM\DataObject;

class FakeSiteTree extends DataObject
{
    private static $db = [
        'Title' => 'Varchar',
        'Content' => 'HTMLText'
    ];
}
