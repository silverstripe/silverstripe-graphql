<?php


namespace SilverStripe\GraphQL\Schema\BulkLoader;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\ConfigurationApplier;
use SilverStripe\GraphQL\Schema\Interfaces\Identifiable;
use SilverStripe\GraphQL\Schema\Schema;

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
        $this->includeList = array_merge($this->includeList, $include);

        return $this;
    }

    /**
     * @param array $exclude
     * @return $this
     */
    public function exclude(array $exclude): self
    {
        $this->excludeList = array_merge($this->excludeList, $exclude);

        return $this;
    }

    /**
     * @param array $config
     * @return mixed|AbstractBulkLoader
     * @throws SchemaBuilderException
     */
    public function applyConfig(array $config)
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
