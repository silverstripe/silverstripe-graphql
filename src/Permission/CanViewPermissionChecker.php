<?php


namespace SilverStripe\GraphQL\Permission;

use DeepCopy\Filter\Filter;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\Filterable;
use SilverStripe\Security\Member;

class CanViewPermissionChecker implements QueryPermissionChecker
{
    /**
     * @param Filterable $list
     * @param Member|null $member
     * @return Filterable
     */
    public function applyToList(Filterable $list, Member $member = null)
    {
        $excludes = [];
        foreach ($list as $record) {
            if (!$record->canView($member)) {
                $excludes[] = $record->ID;
            }
        }

        if (!empty($excludes)) {
            return $list->exclude(['ID' => $excludes]);
        }

        return $list;
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
