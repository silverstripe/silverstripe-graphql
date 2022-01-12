<?php


namespace SilverStripe\GraphQL\Schema\BulkLoader;

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

    /**
     * @param Collection $collection
     * @return Collection
     */
    public function collect(Collection $collection): Collection
    {
        $includedFiles = [];
        $excludedFiles = [];
        foreach ($this->includeList as $include) {
            foreach (glob(Path::join(BASE_PATH, $include)) as $path) {
                $includedFiles[$path] = true;
            }
        }
        foreach ($this->excludeList as $exclude) {
            foreach (glob(Path::join(BASE_PATH, $exclude)) as $path) {
                $excludedFiles[$path] = true;
            }

        }

        foreach ($collection->getFiles() as $file) {
            if (!isset($includedFiles[$file])) {
                $collection->removeFile($file);
            }
            if (isset($excludedFiles[$file])) {
                $collection->removeFile($file);
            }
        }

        return $collection;
    }
}
