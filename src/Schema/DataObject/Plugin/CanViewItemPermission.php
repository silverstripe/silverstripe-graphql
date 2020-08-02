<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;


use SilverStripe\GraphQL\QueryHandler\QueryHandler;

class CanViewItemPermission extends AbstractCanViewPermission
{
    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'canViewItemPermission';
    }

    /**
     * @return array
     */
    protected function getPermissionResolver(): array
    {
        return [static::class, 'permissionCheck'];
    }

    /**
     * @param $obj
     * @param array $args
     * @param array $context
     * @return object|null
     */
    public static function permissionCheck($obj, array $args, array $context)
    {
        $member = $context[QueryHandler::CURRENT_USER] ?? null;
        if (is_object($obj) && method_exists($obj, 'canView')) {
            if (!$obj->canView($member)) {
                return null;
            }
        }

        return $obj;
    }
}
