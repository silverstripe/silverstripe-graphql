<?php


namespace SilverStripe\GraphQL\Modules\AssetAdmin\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Modules\AssetAdmin\Notice;
use SilverStripe\Assets\File;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\UserContextProvider;
use SilverStripe\Versioned\Staged\RecursivePublishable;
use SilverStripe\Versioned\Mode\Versioned;
use InvalidArgumentException;

class PublicationResolver
{
    const ACTION_PUBLISH = 'publish';
    const ACTION_UNPUBLISH = 'unpublish';

    public static function resolvePublishFiles(...$params)
    {
        return PublicationResolver::resolvePublicationOperation(PublicationResolver::ACTION_PUBLISH, ...$params);
    }

    public static function resolveUnpublishFiles(...$params)
    {
        return PublicationResolver::resolvePublicationOperation(PublicationResolver::ACTION_UNPUBLISH, ...$params);
    }

    /**
     * @param string $action
     * @param mixed $object
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return array
     */
    private static function resolvePublicationOperation(
        $action,
        $object,
        array $args,
        $context,
        ResolveInfo $info
    ) {
        if (!isset($args['ids']) || !is_array($args['ids'])) {
            throw new InvalidArgumentException('IDs must be an array');
        }
        $isPublish = $action === PublicationResolver::ACTION_PUBLISH;
        $sourceStage = $isPublish ? Versioned::DRAFT : Versioned::LIVE;
        $force = $args['force'] ?? false;
        $quiet = $args['quiet'] ?? false;
        $result = [];
        $warningMessages = [];
        $idList = $args['ids'];
        $files = Versioned::get_by_stage(File::class, $sourceStage)
            ->byIds($idList);

        // If warning suppression is not on, bundle up all the warnings into a single exception
        if (!$quiet && $files->count() < count($idList ?? [])) {
            $missingIds = array_diff($idList ?? [], $files->column('ID'));
            foreach ($missingIds as $id) {
                $warningMessages[] = sprintf(
                    'File #%s either does not exist or is not on stage %s.',
                    $id,
                    $sourceStage
                );
            }
        }
        $allowedFiles = [];
        // Check permissions
        foreach ($files as $file) {
            $permissionMethod = $isPublish ? 'canPublish' : 'canUnpublish';
            $member = UserContextProvider::get($context);
            if ($file->$permissionMethod($member)) {
                $allowedFiles[] = $file;
            } elseif (!$quiet) {
                $warningMessages[] = sprintf(
                    'User does not have permission to perform this operation on file "%s"',
                    $file->Title
                );
            }
        }

        if (!empty($warningMessages)) {
            throw new InvalidArgumentException(implode('\n', $warningMessages));
        }

        foreach ($allowedFiles as $file) {
            $result[] = $isPublish
                ? PublicationResolver::publishFile($file, $force)
                : PublicationResolver::unpublishFile($file, $force);
        }

        return $result;
    }

    /**
     * @param File $file
     * @param boolean $force
     * @return File|Notice
     */
    private static function publishFile(File $file, $force = false)
    {
        $file->publishRecursive();

        return $file;
    }


    /**
     * @param File|RecursivePublishable $file
     * @param boolean $force
     * @return Notice|File
     */
    private static function unpublishFile(File $file, $force = false)
    {
        // If not forcing, make sure we aren't interfering with any owners
        if (!$force) {
            $ownersCount = PublicationResolver::countLiveOwners($file);
            if ($ownersCount) {
                return new Notice(
                    _t(
                        'SilverStripe\\AssetAdmin\\GraphQL\\Resolvers\\PublicationResolver.OWNER_WARNING',
                        'File "{file}" is used in {count} place|File "{file}" is used in {count} places.',
                        [
                            'file' => $file->Title,
                            'count' => $ownersCount
                        ]
                    ),
                    'HAS_OWNERS',
                    [$file->ID]
                );
            }
        }

        $file->doUnpublish();
        return $file;
    }

    /**
     * Count number of live owners this file uses
     *
     * @param File $file
     * @return int Number of live owners
     */
    private static function countLiveOwners(File $file): int
    {
        // In case no versioning
        if (!$file->hasExtension(RecursivePublishable::class)) {
            return 0;
        }

        // Query live record
        /** @var Versioned|RecursivePublishable $liveRecord */
        $liveRecord = Versioned::get_by_stage(File::class, Versioned::LIVE)->byID($file->ID);
        if ($liveRecord) {
            return $liveRecord
                ->findOwners(false)
                ->count();
        }

        // No live record, no live owners
        return 0;
    }
}
