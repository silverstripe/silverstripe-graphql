<?php


namespace SilverStripe\GraphQL\Schema\BulkLoader;

use InvalidArgumentException;
use SilverStripe\Core\ClassInfo;

class BulkLoaderSet
{
    /**
     * @var AbstractBulkLoader[]
     */
    private $loaders;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * BulkLoaderSet constructor.
     * @param AbstractBulkLoader[] ...$loaders
     * @param Collection|null $collection
     */
    public function __construct(...$loaders, ?Collection $collection = null)
    {
        $this->setLoaders($loaders);
        if ($collection) {
            $this->setCollection($collection);
        } else {
            $this->collection = Collection::create(ClassInfo::allClasses());
        }
    }

    public function addLoader(AbstractBulkLoader $loader)
    {
        $this->loaders[] = $loader;

        return $this;
    }

    public function process(): Collection
    {
        foreach ($this->loaders as $loader) {
            $this->collection = $loader->collect($this->collection);
        }
    }
    public function setLoaders($loaders): self
    {
        foreach ($loaders as $loader) {
            if (!$loader instanceof AbstractBulkLoader) {
                throw new InvalidArgumentException(sprintf(
                    '%s only accepts instances of %s',
                    static::class,
                    AbstractBulkLoader::class
                ));
            }
        }
        $this->loaders = $loaders;
    }

    public function setCollection(Collection $collection): self
    {
        $this->collection = $collection;

        return $this;
    }
}
