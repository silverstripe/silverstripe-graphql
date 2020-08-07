<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;


use SilverStripe\Core\ClassInfo;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\ORM\Filterable;

class CanViewListPermission extends AbstractCanViewPermission
{
    const IDENTIFIER = 'canViewList';

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    protected function getPermissionResolver(): array
    {
        return [static::class, 'permissionCheck'];
    }

    /**
     * @param $obj
     * @param array $args
     * @param array $context
     * @return Filterable|null
     */
    public static function permissionCheck(Filterable $obj, array $args, array $context)
    {
        $member = $context[QueryHandler::CURRENT_USER] ?? null;
        $excludes = [];

        foreach ($obj as $record) {
            if (ClassInfo::hasMethod($record, 'canView') && !$record->canView($member)) {
                $excludes[] = $record->ID;
            }
        }

        if (!empty($excludes)) {
            return $obj->exclude(['ID' => $excludes]);
        }

        return $obj;
    }
}
