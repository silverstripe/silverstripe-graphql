<?php

namespace SilverStripe\GraphQL\Schema\BulkLoader;

use SilverStripe\Core\Injector\Injectable;
use Exception;
use ReflectionClass;
use ReflectionException;

/**
 * Defines a collection of class names paired with file paths
 */
class Collection
{
    use Injectable;

    private array $manifest;

    /**
     * Collection constructor.
     * @param array $manifest An array of classname keys to filepath values ['My\Class' => '/path/to/Class.php']
     * @throws Exception
     */
    public function __construct(array $manifest = [])
    {
        $this->setManifest($manifest);
    }

    /**
     * @param array $manifest
     * @return $this
     * @throws Exception
     */
    public function setManifest(array $manifest): self
    {
        $this->manifest = $manifest;

        return $this;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function removeClass(string $class): self
    {
        unset($this->manifest[$class]);

        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function removeFile(string $path): self
    {
        $class = array_search($path, $this->manifest ?? []);
        unset($this->manifest[$class]);

        return $this;
    }

    /**
     * @return array
     */
    public function getClasses(): array
    {
        return array_keys($this->manifest ?? []);
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        return array_values($this->manifest ?? []);
    }

    /**
     * @return array
     */
    public function getManifest(): array
    {
        return $this->manifest;
    }

    /**
     * @param array $classList
     * @return Collection
     * @throws ReflectionException
     * @throws Exception
     */
    public static function createFromClassList(array $classList): Collection
    {
        $manifest = [];
        foreach ($classList as $class) {
            if (!class_exists($class ?? '')) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            $filePath = $reflection->getFileName();
            if (!$filePath) {
                continue;
            }

            $manifest[$class] = $filePath;
        }

        return new static($manifest);
    }
}
