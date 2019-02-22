<?php


namespace SilverStripe\GraphQL\Permission;

trait PermissionCheckerAware
{
    /**
     * @var PermissionCheckerInterface
     */
    protected $permissionChecker;

    /**
     * @param PermissionCheckerInterface $checker
     * @return $this
     */
    public function setPermissionChecker(PermissionCheckerInterface $checker)
    {
        $this->permissionChecker = $checker;

        return $this;
    }

    /**
     * @return PermissionCheckerInterface
     */
    public function getPermissionChecker()
    {
        return $this->permissionChecker;
    }
}
