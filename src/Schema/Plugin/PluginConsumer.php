<?php


namespace SilverStripe\GraphQL\Schema\Plugin;


use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Registry\PluginRegistry;
use SilverStripe\GraphQL\Schema\Schema;
use Generator;

trait PluginConsumer
{
    /**
     * @var array
     */
    private $plugins = [];

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
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * @return PluginRegistry
     */
    public function getPluginRegistry(): PluginRegistry
    {
        return Injector::inst()->get(PluginRegistry::class);
    }

    /**
     * @return Generator
     * @throws SchemaBuilderException
     */
    public function loadPlugins(): Generator
    {
        foreach ($this->getPlugins() as $pluginName => $config) {
            $plugin = $this->getPluginRegistry()->getPluginByID($pluginName);
            Schema::invariant(
                $plugin,
                'Plugin %s not found',
                $pluginName
            );
            yield [$plugin, $config];
        }
    }

}
