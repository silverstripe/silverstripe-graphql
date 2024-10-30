<?php


namespace SilverStripe\GraphQL\Modules\AssetAdmin\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\AssetAdmin\Controller\AssetAdminFile;
use SilverStripe\GraphQL\Modules\AssetAdmin\FileFilter;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use InvalidArgumentException;
use Exception;
use Closure;
use SilverStripe\ORM\DataQuery;

class FolderTypeResolver
{
    /**
     * @param Folder $object
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return mixed
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function resolveFolderChildren(
        $object,
        array $args,
        $context,
        ResolveInfo $info
    ) {
        // canView() checks on parent folder are implied by the query returning $object
        // Note: The inability to query permissions against the entire set means pagination
        // is inaccurate when any item in the list returns false on canView()

        $filter = (!empty($args['filter'])) ? $args['filter'] : [];

        if (isset($filter['parentId']) && (int)$filter['parentId'] !== (int)$object->ID) {
            throw new InvalidArgumentException(sprintf(
                'The "parentId" value (#%d) needs to match the current object id (#%d)',
                (int)$filter['parentId'],
                (int)$object->ID
            ));
        }

        $list = Versioned::get_by_stage(File::class, 'Stage');
        $filter['parentId'] = $object->ID;
        $list = FileFilter::filterList($list, $filter);

        // Filter by permission
        // DataQuery::column ignores surrogate sorting fields
        // see https://github.com/silverstripe/silverstripe-framework/issues/8926
        // the following line is a workaround for `$ids = $list->column('ID');`
        $ids = $list->dataQuery()->execute()->column('ID');

        $permissionChecker = File::singleton()->getPermissionChecker();
        $member = UserContextProvider::get($context);
        $canViewIDs = array_keys(array_filter($permissionChecker->canViewMultiple(
            $ids,
            $member
        ) ?? []));
        // Filter by visible IDs (or force empty set if none are visible)
        // Remove the limit as it no longer applies. We've already filtered down to the exact
        // IDs we need.
        $canViewList = $list->filter('ID', $canViewIDs ?: 0)
            ->limit(null);

        return $canViewList;
    }

    /**
     * @param Folder|AssetAdminFile $object
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return int
     */
    public static function resolveFolderDescendantFileCount($object, array $args, $context, ResolveInfo $info)
    {
        return $object->getDescendantFileCount();
    }

    /**
     * @param Folder|AssetAdminFile $object
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return int
     */
    public static function resolveFolderFilesInUseCount($object, array $args, $context, ResolveInfo $info)
    {
        return $object->getFilesInUse()->count();
    }

    /**
     * @param File $object
     * @param array $args
     * @param array $context
     * @param ResolveInfo $info
     * @return File[]
     */
    public static function resolveFolderParents($object, array $args, $context, ResolveInfo $info)
    {
        $parents = [];
        $next = $object->Parent();
        while ($next && $next->isInDB()) {
            array_unshift($parents, $next);
            if ($next->ParentID) {
                $next = $next->Parent();
            } else {
                break;
            }
        }

        return $parents;
    }

    /**
     * @param array $context
     * @return Closure
     */
    public static function sortChildren(array $context): Closure
    {
        $fieldName = $context['fieldName'];
        return function (?DataList $list, array $args) use ($fieldName) {
            if ($list === null) {
                return null;
            }

            $sortArgs = $args[$fieldName] ?? [];

            $list = $list->alterDataQuery(static function (DataQuery $dataQuery) use ($sortArgs) {
                $query = $dataQuery->query();
                $existingOrderBys = [];
                foreach ($query->getOrderBy() as $field => $direction) {
                    if (strpos($field, '.') === false) {
                        // some fields may be surrogates added by extending augmentSQL
                        // we have to preserve those expressions rather than auto-generated names
                        // that SQLSelect::addOrderBy leaves for them (e.g. _SortColumn0)
                        $field = $query->expressionForField(trim($field, '"')) ?: $field;
                    }
                    $existingOrderBys[$field] = $direction;
                }

                // Folders always go first
                $dataQuery->sort(
                    sprintf(
                        '(CASE WHEN "ClassName"=%s THEN 1 ELSE 0 END)',
                        DB::get_conn()->quoteString(Folder::class)
                    ),
                    'DESC',
                    true
                );

                foreach ($sortArgs as $field => $dir) {
                    $normalised = FieldAccessor::singleton()->normaliseField(File::singleton(), $field);
                    Schema::invariant(
                        $normalised,
                        'Could not find field %s on %s',
                        $field,
                        File::class
                    );
                    $dataQuery->sort($normalised, $dir, false);
                }

                // respect default_sort
                foreach ($existingOrderBys as $field => $dir) {
                    $dataQuery->sort($field, $dir, false);
                }

                return $dataQuery;
            });
            return $list;
        };
    }
}
