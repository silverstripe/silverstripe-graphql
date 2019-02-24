<?php


namespace SilverStripe\GraphQL\Permission;

trait PermissionCheckerAware
{
    /**
     * @var QueryPermissionChecker
     */
    protected $permissionChecker;

    /**
     * @param QueryPermissionChecker $checker
     * @return $this
     */
    public function setPermissionChecker(QueryPermissionChecker $checker)
    {
        $this->permissionChecker = $checker;

        return $this;
    }

    /**
     * @return QueryPermissionChecker
     */
    public function getPermissionChecker()
    {
        return $this->permissionChecker;
    }
}
