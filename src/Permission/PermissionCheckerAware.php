<?php


namespace SilverStripe\GraphQL\Permission;

use SilverStripe\Core\Injector\Injector;

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
        $checker = $this->permissionChecker;
        if (is_null($checker)) {
            return Injector::inst()->get(QueryPermissionChecker::class . '.default');
        }
        return $checker;
    }
}
