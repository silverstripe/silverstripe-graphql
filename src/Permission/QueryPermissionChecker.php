<?php

namespace SilverStripe\GraphQL\Permission;

use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Member;

/**
 * Defines a service that can filter a list with a permission check against a member
 */
interface QueryPermissionChecker
{
    /**
     * @param SS_List $list
     * @param Member|null $member
     * @return SS_List
     */
    public function applyToList(SS_List $list, Member $member = null);

    /**
     * @param object $item
     * @param Member|null $member
     * @return bool
     */
    public function checkItem($item, Member $member = null);
}
