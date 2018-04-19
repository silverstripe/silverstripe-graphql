<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Member;

class StaffMemberFake extends Member implements TestOnly
{
    private static $table_name = 'GraphQL_StaffMemberFake';

    private static $db = [
        'StaffID' => 'Varchar'
    ];
}
