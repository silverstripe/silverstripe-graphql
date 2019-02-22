<?php


namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\GraphQL\Permission\PermissionCheckerInterface;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Member;

class FakePermissionChecker implements PermissionCheckerInterface
{
    protected $shouldAllow = true;

    public function __construct($shouldAllow = true)
    {
        $this->shouldAllow = $shouldAllow;
    }

    public function applyToList(SS_List $list, Member $member = null)
    {
        return $this->shouldAllow ? $list : new ArrayList();
    }

    public function checkItem($item, Member $member = null)
    {
        return $this->shouldAllow;
    }
}
