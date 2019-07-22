<?php


namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\GraphQL\Permission\QueryPermissionChecker;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Filterable;
use SilverStripe\Security\Member;

class FakeQueryPermissionChecker implements QueryPermissionChecker
{
    protected $shouldAllow = true;

    public function __construct($shouldAllow = true)
    {
        $this->shouldAllow = $shouldAllow;
    }

    public function applyToList(Filterable $list, Member $member = null)
    {
        return $this->shouldAllow ? $list : new ArrayList();
    }

    public function checkItem($item, Member $member = null)
    {
        return $this->shouldAllow;
    }
}
