<?php

namespace SilverStripe\GraphQL\Permission;

use SilverStripe\ORM\Filterable;
use SilverStripe\Security\Member;

/**
 * Defines a service that can filter a list with a permission check against a member
 */
interface QueryPermissionChecker
{
    /**
     * @param Filterable $list
     * @param Member|null $member
     * @return Filterable
     */
    public function applyToList(Filterable $list, Member $member = null);

    /**
     * @param object $item
     * @param Member|null $member
     * @return bool
     */
    public function checkItem($item, Member $member = null);
}
