<?php


namespace SilverStripe\GraphQL\Schema\DataObject\Plugin;

use InvalidArgumentException;

class CanViewPaginatedPermission extends AbstractCanViewPermission
{
    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'canViewPaginatedPermission';
    }

    /**
     * @return array
     */
    protected function getPermissionResolver(): array
    {
        return [static::class, 'permissionCheck'];
    }

    /**
     * @param array $obj
     * @param array $args
     * @param array $context
     * @return array
     */
    public static function permissionCheck(array $obj, array $args, array $context): array
    {
        if (!is_array($obj) || !isset($obj['nodes']) || !isset($obj['edges'])) {
            throw new InvalidArgumentException(sprintf(
                'Permission checker %s was applied to a query that does not appear to return
                a paginated list. Make sure this plugin is listed after the pagination plugin',
                __CLASS__
            ));
        }

        $list = $obj['nodes'];
        $originalCount = $list->count();
        $filteredList = CanViewListPermission::permissionCheck($list, $args, $context);
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
}
