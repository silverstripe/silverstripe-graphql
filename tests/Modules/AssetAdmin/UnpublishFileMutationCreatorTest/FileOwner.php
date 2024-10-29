<?php

namespace SilverStripe\GraphQL\Tests\Modules\AssetAdmin\UnpublishFileMutationCreatorTest;

use SilverStripe\Assets\File;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Staged\RecursivePublishable;
use SilverStripe\Versioned\Mode\Versioned;

/**
 * @mixin RecursivePublishable
 * @mixin Versioned
 * @property int $OwnedFileID
 */
class FileOwner extends DataObject implements TestOnly
{
    private static $table_name = 'UnpublishFileMutationCreatorTest_FileOwner';

    private static $extensions = [
        Versioned::class,
    ];

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'OwnedFile' => File::class,
    ];
}
