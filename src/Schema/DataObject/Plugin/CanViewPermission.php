<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;


use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\ClassInfo;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Filterable;
use InvalidArgumentException;
use SilverStripe\ORM\SS_List;

/**
 * A permission checking plugin for DataLists
 */
class CanViewPermission extends AbstractCanViewPermission
{
    const IDENTIFIER = 'canView';

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
     * @return Filterable|object|array|null
     * @throws InvalidArgumentException
     */
    public static function permissionCheck($obj, array $args, array $context, ResolveInfo $info)
    {
        if (is_array($obj)) {
            return static::paginatedPermissionCheck($obj, $args, $context, $info);
        }

        if ($obj instanceof Filterable) {
            return static::listPermissionCheck($obj, $args, $context, $info);
        }

        if (is_object($obj)) {
            return static::itemPermissionCheck($obj, $args, $context, $info);
        }

        throw new InvalidArgumentException(sprintf(
            'Plugin "%s" cannot be applied to field "%s" because it does not resolve to an object, array,
            or implementation of %s. You may need to implement a custom permission checker that extends %s.
            Otherwise, try returning an instance of %s or another implementation of %s.',
            self::IDENTIFIER,
            $info->fieldName,
            Filterable::class,
            AbstractCanViewPermission::class,
            SS_List::class,
            Filterable::class
        ));

    }

    /**
     * @param array $obj
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return array
     */
    public static function paginatedPermissionCheck(array $obj, array $args, array $context, ResolveInfo $info): array
    {
        if (!isset($obj['nodes'])) {
            throw new InvalidArgumentException(sprintf(
                'Permission checker "%s" cannot be applied to field "%s" because it resolves to an array
                 that does not appear to be a paginated list. Make sure this plugin is listed after the pagination plugin
                 using the "after: %s" syntax in your config. If you are trying to check permissions on a simple array
                 of data, you will need to implement a custom permission checker that extends %s',
                self::IDENTIFIER,
                $info->fieldName,
                Paginator::IDENTIFIER,
                AbstractCanViewPermission::class
            ));
        }

        $list = $obj['nodes'];
        $originalCount = $list->count();
        $filteredList = CanViewPermission::permissionCheck($list, $args, $context, $info);
        $newCount = $filteredList->count();
        if ($originalCount === $newCount) {
            return $obj;
        }
        $obj['nodes'] = $filteredList;
        $edges = [];
        foreach ($filteredList as $record) {
            $edges[] = ['node' => $record];
        }
        $obj['edges'] = $edges;

        return $obj;

    }

    /**
     * @param $obj
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return object|null
     */
    public static function itemPermissionCheck($obj, array $args, array $context, ResolveInfo $info)
    {
        $member = $context[QueryHandler::CURRENT_USER] ?? null;
        if (is_object($obj) && method_exists($obj, 'canView')) {
            if (!$obj->canView($member)) {
                return null;
            }
        }

        return $obj;
    }

    /**
     * @param Filterable $obj
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return Filterable
     */
    public static function listPermissionCheck(Filterable $obj, array $args, array $context, ResolveInfo $info): Filterable
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
