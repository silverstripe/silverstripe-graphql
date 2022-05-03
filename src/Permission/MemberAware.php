<?php


namespace SilverStripe\GraphQL\Permission;

use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Allows any class to hold Member state
 */
trait MemberAware
{

    private ?Member $member = null;

    /**
     * Set the Member for the current context
     *
     * @param  Member $member
     */
    public function setMemberContext(?Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the Member for the current context either from a previously set value or the current user
     *
     * @return Member
     */
    public function getMemberContext(): ?Member
    {
        return $this->member ?: Security::getCurrentUser();
    }
}
