<?php

namespace SilverStripe\GraphQL\Schema\BulkLoader;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\ClassInfo;
use ReflectionException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Logger;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * Composed with a list of bulk loaders to be executed in serial and return the aggregate result
 * of all their collect() calls
 */
class BulkLoaderSet implements ConfigurationApplier
{
    use Injectable;

    /**
     * @var AbstractBulkLoader[]
     */
    private array $loaders;

    private Collection $initialCollection;

    /**
     * BulkLoaderSet constructor.
     * @param AbstractBulkLoader[] $loaders
     * @param Collection|null $initialCollection
     * @throws ReflectionException
     */
    public function __construct(array $loaders = [], ?Collection $initialCollection = null)
    {
        $this->setLoaders($loaders);
        if ($initialCollection) {
            $this->initialCollection = $initialCollection;
        } else {
            $this->initialCollection = Collection::createFromClassList(ClassInfo::allClasses());
        }
    }

    /**
     * @param array $config
     * @return $this
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config): self
    {
        $registry = Registry::inst();
        foreach ($config as $loaderID => $loaderConfig) {
            /* @var AbstractBulkLoader $loader */
            $loader = $registry->getByID($loaderID);
            Schema::invariant($loader, 'Loader "%s" does not exist', $loaderID);
            $loader->applyConfig($loaderConfig);
            $this->addLoader($loader);
        }

        return $this;
    }

    /**
     * @param AbstractBulkLoader $loader
     * @return $this
     */
    public function addLoader(AbstractBulkLoader $loader): self
    {
        $this->loaders[] = $loader;

        return $this;
    }

    /**
     * @return Collection
     */
    public function process(): Collection
    {
        $logger = Injector::inst()->get(LoggerInterface::class . '.graphql-build');
        $collection = $this->initialCollection;
        $logger->debug(sprintf(
            'Bulk loader initial collection size: %s',
            count($collection->getClasses() ?? [])
        ));
        foreach ($this->loaders as $loader) {
            $collection = $loader->collect($collection);
            $logger->debug(sprintf(
                'Loader %s reduced bulk load to %s',
                $loader->getIdentifier(),
                count($collection->getClasses() ?? [])
            ));
        }

        return $collection;
    }

    /**
     * @param array $loaders
     * @return $this
     */
    public function setLoaders(array $loaders): self
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

        return $this;
    }

    /**
     * @return AbstractBulkLoader[]
     */
    public function getLoaders(): array
    {
        return $this->loaders;
    }
}
