<?php

namespace SilverStripe\GraphQL\Schema\BulkLoader;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Interfaces\Identifiable;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * Provides base functionality to all bulk loaders. Should override the collect()
 * method with computations that parse the include/exclude directives and return
 * a collection of classes.
 */
abstract class AbstractBulkLoader implements Identifiable, ConfigurationApplier
{
    use Injectable;

    /**
     * @var string[]
     */
    protected array $includeList;

    /**
     * @var string[]
     */
    protected array $excludeList;

    /**
     * AbstractBulkLoader constructor.
     * @param array $include
     * @param array $exclude
     */
    public function __construct(array $include = [], array $exclude = [])
    {
        $this->includeList = $include;
        $this->excludeList = $exclude;
    }

    /**
     * @param array $include
     * @return $this
     */
    public function include(array $include): self
    {
        $this->includeList = $include;

        return $this;
    }

    /**
     * @param array $exclude
     * @return $this
     */
    public function exclude(array $exclude): self
    {
        $this->excludeList = $exclude;

        return $this;
    }

    /**
     * @param array $config
     * @return AbstractBulkLoader
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config): self
    {
        Schema::assertValidConfig($config, ['include', 'exclude'], ['include']);
        $include = $config['include'];
        $exclude = $config['exclude'] ?? [];
        if (!is_array($include)) {
            $include = [$include];
        }
        if (!is_array($exclude)) {
            $exclude = [$exclude];
        }
        return $this
            ->include($include)
            ->exclude($exclude);
    }

    /**
     * @param Collection $collection
     * @return Collection
     */
    public function collect(Collection $collection): Collection
    {
        return Collection::create($collection->getManifest());
    }

    /**
     * @return string
     */
    abstract public static function getIdentifier(): string;
}
