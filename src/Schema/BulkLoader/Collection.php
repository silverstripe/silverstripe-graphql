<?php


namespace SilverStripe\GraphQL\Schema\BulkLoader;

use Composer\Autoload\ClassLoader;
use SilverStripe\Core\Injector\Injectable;
use Exception;
use ReflectionClass;

/**
 * Defines a collection of class names paired with file paths
 */
class Collection
{
    use Injectable;

    /**
     * @var array
     */
    private $manifest;

    /**
     * Collection constructor.
     * @param array $classList
     * @throws Exception
     */
    public function __construct(array $classList)
    {
        $this->setClassList($classList);
    }

    /**
     * An expensive operation that rebuilds the index of className -> filePath
     * @param array $classList
     * @return $this
     * @throws Exception
     */
    public function setClassList(array $classList): self
    {
        $manifest = [];
        foreach ($classList as $class) {
            if (!class_exists($class)) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            $filePath = $reflection->getFileName();
            if (!$filePath) {
                continue;
            }

            $manifest[$class] = $filePath;
        }

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
        $class = array_search($path, $this->manifest);
        unset($this->manifest[$class]);

        return $this;
    }

    /**
     * @return array
     */
    public function getClasses(): array
    {
        return array_keys($this->manifest);
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        return array_values($this->manifest);
    }
}
