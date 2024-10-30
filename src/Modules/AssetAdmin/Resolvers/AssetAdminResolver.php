<?php


namespace SilverStripe\GraphQL\Modules\AssetAdmin\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\AssetAdmin\Controller\AssetAdminFile;
use SilverStripe\GraphQL\Modules\AssetAdmin\FileFilter;
use SilverStripe\GraphQL\Modules\AssetAdmin\Notice;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\ORM\DataList;
use SilverStripe\Versioned\Versioned;
use InvalidArgumentException;

class AssetAdminResolver
{
    public static function resolveFileInterfaceType($object)
    {
        if ($object instanceof Folder) {
            return 'Folder';
        }
        if ($object instanceof File) {
            return 'File';
        }
    }

    public static function resolveCreateFile($object, array $args, $context, ResolveInfo $info)
    {
        $accessor = FieldAccessor::singleton();
        $parentID = isset($args['file']['parentId']) ? intval($args['file']['parentId']) : 0;
        if ($parentID) {
            $parent = Versioned::get_by_stage(Folder::class, Versioned::DRAFT)->byID($parentID);
            if (!$parent) {
                throw new InvalidArgumentException(sprintf(
                    '%s#%s not found',
                    Folder::class,
                    $parentID
                ));
            }
        }

        $canCreateContext = [];
        foreach ($args['file'] as $name => $val) {
            $canCreateContext[$accessor->normaliseField(File::singleton(), $name)] = $val;
        }
        $member = UserContextProvider::get($context);
        if (!File::singleton()->canCreate($member, $canCreateContext)) {
            throw new InvalidArgumentException(sprintf(
                '%s# create not allowed',
                File::class
            ));
        }

        $file = File::create();
        foreach ($args['file'] as $name => $val) {
            $field = $accessor->normaliseField($file, $name);
            $file->$field = $val;
        }

        $file->writeToStage(Versioned::DRAFT);

        return $file;
    }

    public static function resolveCreateFolder($object, array $args, $context, ResolveInfo $info)
    {
        $accessor = FieldAccessor::singleton();
        $parentID = isset($args['folder']['parentId']) ? intval($args['folder']['parentId']) : 0;
        if ($parentID) {
            $parent = Versioned::get_by_stage(Folder::class, Versioned::DRAFT)->byID($parentID);
            if (!$parent) {
                throw new InvalidArgumentException(sprintf(
                    '%s#%s not found',
                    Folder::class,
                    $parentID
                ));
            }
        }

        // Check permission
        $canCreateContext = [];
        foreach ($args['folder'] as $name => $val) {
            $canCreateContext[$accessor->normaliseField(Folder::singleton(), $name)] = $val;
        }
        if (!Folder::singleton()->canCreate($context['currentUser'] ?? null, $canCreateContext)) {
            throw new InvalidArgumentException(sprintf(
                '%s create not allowed',
                Folder::class
            ));
        }

        $folder = Folder::create();
        foreach ($args['folder'] as $name => $val) {
            $field = $accessor->normaliseField($folder, $name);
            $folder->$field = $val;
        }

        $folder->writeToStage(Versioned::DRAFT);

        return $folder;
    }

    public static function resolveDeleteFiles($object, array $args, $context, ResolveInfo $info)
    {
        if (!isset($args['ids']) || !is_array($args['ids'])) {
            throw new InvalidArgumentException('ids must be an array');
        }
        $idList = $args['ids'];

        $files = Versioned::get_by_stage(File::class, Versioned::DRAFT)->byIDs($idList);
        if ($files->count() < count($idList ?? [])) {
            // Find out which files count not be found
            $missingIds = array_diff($idList ?? [], $files->column('ID'));
            throw new InvalidArgumentException(sprintf(
                '%s items %s are not found',
                File::class,
                implode(', ', $missingIds)
            ));
        }

        $deletedIDs = [];
        $member = UserContextProvider::get($context);
        foreach ($files as $file) {
            if ($file->canDelete($member)) {
                $file->doArchive();
                $deletedIDs[] = $file->ID;
            }
        }

        return $deletedIDs;
    }

    public static function resolveMoveFiles($object, array $args, $context)
    {
        $folderId = (isset($args['folderId'])) ? $args['folderId'] : 0;
        $member = UserContextProvider::get($context);

        if ($folderId) {
            $folder = Versioned::get_by_stage(Folder::class, Versioned::DRAFT)
                ->byID($folderId);
            if (!$folder) {
                throw new InvalidArgumentException(sprintf(
                    '%s#%s not found',
                    Folder::class,
                    $folderId
                ));
            }

            // Check permission
            if (!$folder->canEdit($member)) {
                throw new InvalidArgumentException(sprintf(
                    '%s edit not allowed',
                    Folder::class
                ));
            }
        }
        $files = Versioned::get_by_stage(File::class, Versioned::DRAFT)
            ->byIDs($args['fileIds']);
        $errorFiles = [];
        foreach ($files as $file) {
            if ($file->canEdit($member)) {
                $file->ParentID = $folderId;
                $file->writeToStage(Versioned::DRAFT);
            } else {
                $errorFiles[] = $file->ID;
            }
        }

        if ($errorFiles) {
            throw new InvalidArgumentException(sprintf(
                '%s (%s) edit not allowed',
                File::class,
                implode(', ', $errorFiles)
            ));
        }

        if (!isset($folder)) {
            return Folder::singleton();
        }
        return $folder;
    }


    public static function resolvePublicationNotice($value, array $args, array $context, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        $method = 'get'.$fieldName;
        if (method_exists($value, $method ?? '')) {
            return $value->$method();
        }

        throw new \Exception(sprintf(
            'Invalid field %s on %s',
            $fieldName,
            get_class($value)
        ));
    }

    /**
     * @param $value
     * @return string
     */
    public static function resolvePublicationResultUnion($value): string
    {
        if ($value instanceof File) {
            return 'File';
        }
        if ($value instanceof Notice) {
            return 'PublicationNotice';
        }
    }

    public static function resolveReadDescendantFileCounts($object, array $args, $context, ResolveInfo $info): array
    {
        if (!isset($args['ids']) || !is_array($args['ids'])) {
            throw new \InvalidArgumentException('ids must be an array');
        }
        $ids = $args['ids'];

        $files = Versioned::get_by_stage(File::class, Versioned::DRAFT)->byIDs($ids);
        if ($files->count() < count($ids ?? [])) {
            $class = File::class;
            $missingIds = implode(', ', array_diff($ids ?? [], $files->column('ID')));
            throw new \InvalidArgumentException("{$class} items {$missingIds} are not found");
        }

        $data = [];
        foreach ($files as $file) {
            if (!$file->canView($context['currentUser'])) {
                continue;
            }
            $data[] = [
                'id' => $file->ID,
                'count' => $file->getDescendantFileCount()
            ];
        }
        return $data;
    }

    public static function resolveReadFileUsage($object, array $args, $context, ResolveInfo $info): array
    {
        if (!isset($args['ids']) || !is_array($args['ids'])) {
            throw new InvalidArgumentException('ids must be an array');
        }
        $idList = $args['ids'];

        $files = Versioned::get_by_stage(File::class, Versioned::DRAFT)->byIDs($idList);
        if ($files->count() < count($idList ?? [])) {
            // Find out which files count not be found
            $missingIds = array_diff($idList ?? [], $files->column('ID'));
            throw new InvalidArgumentException(sprintf(
                '%s items %s are not found',
                File::class,
                implode(', ', $missingIds)
            ));
        }

        $usage = [];
        $member = UserContextProvider::get($context);
        foreach ($files as $file) {
            if ($file->canView($member)) {
                $useEntry = ['id' => $file->ID];
                $useEntry['inUseCount'] = $file instanceof Folder ?
                    $file->getFilesInUse()->count():
                    $file->BackLinkTrackingCount();
                $usage[] = $useEntry;
            }
        }

        return $usage;
    }

    /**
     * @param $object
     * @param array $args
     * @param $context
     * @param $info
     * @return DataList<File>
     * @throws HTTPResponse_Exception
     */
    public static function resolveReadFiles($object, array $args = [], $context = [], $info = null)
    {
        $filter = (!empty($args['filter'])) ? $args['filter'] : [];
        $member = UserContextProvider::get($context);

        // Permission checks
        $parent = Folder::singleton();
        if (isset($filter['parentId']) && $filter['parentId'] !== 0) {
            $parent = Folder::get()->byID($filter['parentId']);
            if (!$parent) {
                throw new InvalidArgumentException(sprintf(
                    '%s#%s not found',
                    Folder::class,
                    $filter['parentId']
                ));
            }
        }
        if (!$parent->canView($member)) {
            throw new InvalidArgumentException(sprintf(
                '%s#%s view access not permitted',
                Folder::class,
                $parent->ID
            ));
        }

        if (isset($filter['recursive']) && $filter['recursive']) {
            throw new InvalidArgumentException((
            'The "recursive" flag can only be used for the "children" field'
            ));
        }

        // Filter list
        $list = Versioned::get_by_stage(File::class, Versioned::DRAFT);
        $list = FileFilter::filterList($list, $filter);

        // Permission checks
        $list = $list->filterByCallback(function (File $file) use ($context, $member) {
            return $file->canView($member);
        });

        return $list;
    }
}
