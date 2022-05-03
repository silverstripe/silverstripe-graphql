<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Core\ClassInfo;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Filterable;
use InvalidArgumentException;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;

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

    protected function getPermissionResolver(): callable
    {
        return [static::class, 'permissionCheck'];
    }

    /**
     * @param mixed $obj
     * @return Filterable|object|array|null
     * @throws InvalidArgumentException
     */
    public static function permissionCheck($obj, array $args, array $context, ResolveInfo $info)
    {
        if ($obj === null) {
            return null;
        }

        if (is_array($obj)) {
            if (isset($obj['nodes'])) {
                return static::paginatedPermissionCheck($obj, $args, $context, $info);
            }
            // This is just arbitrary array data (either a list or a single record).
            // Either way, we have no way of checking canView() and should assume it's viewable.
            return $obj;
        }


        if (is_object($obj)) {
            return $obj instanceof Filterable
                ? static::listPermissionCheck($obj, $args, $context, $info)
                : static::itemPermissionCheck($obj, $args, $context, $info);
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
     */
    public static function paginatedPermissionCheck(array $obj, array $args, array $context, ResolveInfo $info): array
    {
        $list = $obj['nodes'];
        $originalCount = count($list ?? []);
        $filteredList = static::permissionCheck($list, $args, $context, $info);
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
     * @param mixed $obj
     */
    public static function itemPermissionCheck($obj, array $args, array $context, ResolveInfo $info): ?object
    {
        $member = UserContextProvider::get($context);
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
        $member = UserContextProvider::get($context);
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
