<?php


namespace SilverStripe\GraphQL\Schema\Plugin;


use MJS\TopSort\CircularDependencyException;
use MJS\TopSort\ElementNotFoundException;
use MJS\TopSort\Implementations\ArraySort;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\PluginValidator;
use SilverStripe\GraphQL\Schema\Registry\PluginRegistry;
use SilverStripe\GraphQL\Schema\Schema;
use Generator;

/**
 * Allows adding, loading, and sorting of plugins
 */
trait PluginConsumer
{
    /**
     * @var array
     */
    private $plugins = [];

    /**
     * @var array
     */
    private $defaultPlugins = [];

    /**
     * @param string $pluginName
     * @param $config
     * @return $this
     */
    public function addPlugin(string $pluginName, $config): self
    {
        $this->plugins[$pluginName] = $config;

        return $this;
    }

    /**
     * @param string $pluginName
     * @return $this
     */
    public function removePlugin(string $pluginName): self
    {
        unset($this->plugins[$pluginName]);

        return $this;
    }

    /**
     * @param array $plugins
     * @return $this
     */
    public function mergePlugins(array $plugins): self
    {
        foreach ($plugins as $identifier => $config) {
            if (isset($this->plugins[$identifier])) {
                $this->plugins[$identifier] = array_merge(
                    $this->plugins[$identifier],
                    $config
                );
            } else {
                $this->plugins[$identifier] = $config;
            }
        }

        return $this;
    }

    /**
     * @param array $plugins
     * @return $this
     * @throws SchemaBuilderException
     */
    public function setPlugins(array $plugins): self
    {
        Schema::assertValidConfig($plugins);
        foreach ($plugins as $pluginName => $config) {
            if ($config === false) {
                continue;
            }
            $pluginConfig = $config === true ? [] : $config;
            $this->addPlugin($pluginName, $pluginConfig);
        }

        return $this;
    }

    /**
     * @param array $plugins
     * @return $this
     * @throws SchemaBuilderException
     */
    public function setDefaultPlugins(array $plugins): self
    {
        Schema::assertValidConfig($plugins);
        foreach ($plugins as $pluginName => $config) {
            if ($config === false) {
                continue;
            }
            $pluginConfig = $config === true ? [] : $config;
            $this->defaultPlugins[$pluginName] = $pluginConfig;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getPlugins(): array
    {
        return array_replace_recursive($this->defaultPlugins, $this->plugins);
    }


    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPlugin(string $identifier): bool
    {
        $ids = array_keys($this->getPlugins());

        return in_array($identifier, $ids);
    }

    /**
     * @return PluginRegistry
     */
    public function getPluginRegistry(): PluginRegistry
    {
        return Injector::inst()->get(PluginRegistry::class);
    }

    /**
     * Translates all the ID and config settings to first class instances
     *
     * @return Generator
     * @throws SchemaBuilderException
     * @throws CircularDependencyException
     * @throws ElementNotFoundException
     */
    public function loadPlugins(): Generator
    {
        foreach ($this->getSortedPlugins() as $pluginData) {
            $pluginName = $pluginData['name'];
            $config = $pluginData['config'];
            $plugin = $this->getPluginRegistry()->getPluginByID($pluginName);
            if ($this instanceof PluginValidator) {
                $this->validatePlugin($pluginName, $plugin);
            } else {
                Schema::invariant(
                    $plugin,
                    'Plugin %s not found',
                    $pluginName
                );
            }
            yield [$plugin, $config];
        }
    }

    /**
     * Sorts the before/after of plugins using topological sort
     *
     * @return array
     * @throws CircularDependencyException
     * @throws ElementNotFoundException
     */
    protected function getSortedPlugins(): array
    {
        $dependencies = [];
        $beforeAll = [];
        $afterAll = [];
        $allPlugins = $this->getPlugins();
        $allPluginNames = array_keys($allPlugins);
        foreach ($allPlugins as $pluginName => $pluginConfig) {
            $before = $pluginConfig['before'] ?? [];
            if ($before === Schema::ALL) {
                $beforeAll[] = $pluginName;
                $allPluginNames = array_filter($allPluginNames, function ($name) use ($pluginName) {
                    return $name !== $pluginName;
                });
                continue;
            }
            $before = !is_array($before) ? [$before] : $before;
            $before = array_intersect($before, $allPluginNames);

            $after = $pluginConfig['after'] ?? [];
            if ($after === Schema::ALL) {
                $afterAll[] = $pluginName;
                $allPluginNames = array_filter($allPluginNames, function ($name) use ($pluginName) {
                    return $name !== $pluginName;
                });
                continue;
            }
            $after = !is_array($after) ? [$after] : $after;
            $after = array_intersect($after, $allPluginNames);

            if (!isset($dependencies[$pluginName])) {
                $dependencies[$pluginName] = [];
            }
            $dependencies[$pluginName] = array_merge($dependencies[$pluginName], $after);

            foreach ($before as $dependant) {
                if (!isset($dependencies[$dependant])) {
                    $dependencies[$dependant] = [];
                }
                $dependencies[$dependant][] = $pluginName;
            }
        }
        $sorter = new ArraySort($dependencies);

        $middle = $sorter->sort();

        $sorted = array_merge(
            $beforeAll,
            $middle,
            $afterAll
        );
        $map = [];
        foreach ($sorted as $pluginName) {
            $map[] = [
                'name' => $pluginName,
                'config' => $allPlugins[$pluginName] ?? [],
            ];
        }

        return $map;
    }

}
