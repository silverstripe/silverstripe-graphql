<?php


namespace SilverStripe\GraphQL\Permission;


use SilverStripe\Security\Member;

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
