<?php


namespace SilverStripe\GraphQL\Schema\BulkLoader;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Interfaces\Identifiable;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * Provides base functionality to all bulk loaders. Must define a collect() method.
 */
abstract class AbstractBulkLoader implements Identifiable, ConfigurationApplier
{
    use Injectable;

    /**
     * @var string[]
     */
    protected $includeList;

    /**
     * @var string[]
     */
    protected $excludeList;

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
    abstract public function collect(Collection $collection): Collection;

    /**
     * @return string
     */
    abstract public static function getIdentifier(): string;
}
