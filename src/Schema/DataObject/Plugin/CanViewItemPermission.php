<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;


use SilverStripe\GraphQL\QueryHandler\QueryHandler;

/**
 * A plugin that checks permission for a DataObject item
 */
class CanViewItemPermission extends AbstractCanViewPermission
{
    const IDENTIFIER = 'canViewItem';
    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
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
