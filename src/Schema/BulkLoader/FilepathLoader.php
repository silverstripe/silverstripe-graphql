<?php

namespace SilverStripe\GraphQL\Schema\BulkLoader;

use SilverStripe\Control\Director;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Core\Path;

/**
 * Loads classes by fuzzy match (glob), relative to the root e.g. `src/*.model.php`
 */
class FilepathLoader extends AbstractBulkLoader
{
    const IDENTIFIER = 'filepathLoader';

    public static function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function collect(Collection $collection): Collection
    {
        $newCollection = parent::collect($collection);
        $includedFiles = [];
        $excludedFiles = [];

        foreach ($this->includeList as $include) {
            $resolvedDir = ModuleResourceLoader::singleton()->resolvePath($include);
            $absGlob = Director::is_absolute($include) ? $include : Path::join(BASE_PATH, $resolvedDir);

            foreach (glob(Path::join($absGlob) ?? '') as $path) {
                $includedFiles[$path] = true;
            }
        }
        foreach ($this->excludeList as $exclude) {
            $resolvedDir = ModuleResourceLoader::singleton()->resolvePath($exclude);
            $absGlob = Director::is_absolute($exclude) ? $exclude : Path::join(BASE_PATH, $resolvedDir);

            foreach (glob(Path::join($absGlob) ?? '') as $path) {
                $excludedFiles[$path] = true;
            }
        }

        foreach ($collection->getFiles() as $file) {
            $isIncluded = isset($includedFiles[$file]) && !isset($excludedFiles[$file]);
            if (!$isIncluded) {
                $newCollection->removeFile($file);
            }
        }

        return $newCollection;
    }
}
