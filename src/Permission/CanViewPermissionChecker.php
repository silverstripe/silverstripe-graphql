<?php


namespace SilverStripe\GraphQL\Permission;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Member;

class CanViewPermissionChecker implements PermissionCheckerInterface
{
    /**
     * @param SS_List $list
     * @param Member|null $member
     * @return ArrayList|SS_List
     */
    public function applyToList(SS_List $list, Member $member = null)
    {
        /* @var DataList|ArrayList $list */
        return $list->filterByCallback(function (DataObject $item) use ($member) {
            return $item->canView($member);
        });
    }

    /**
     * @param object $item
     * @param Member|null $member
     * @return bool
     */
    public function checkItem($item, Member $member = null)
    {
        if (is_object($item) && method_exists($item, 'canView')) {
            return $item->canView($member);
        }

        return true;
    }
}
