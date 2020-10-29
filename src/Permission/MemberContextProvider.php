<?php


namespace SilverStripe\GraphQL\Permission;

use SilverStripe\Security\Member;

/**
 * Implementors of this interface can hold Member state
 */
interface MemberContextProvider
{
    /**
     * @param Member $member
     */
    public function setMemberContext(Member $member): void;

    /**
     * @return Member|null
     */
    public function getMemberContext(): ?Member;
}
