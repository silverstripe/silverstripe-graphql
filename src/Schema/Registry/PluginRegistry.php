<?php


namespace SilverStripe\GraphQL\Schema\Registry;


use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\PluginInterface;
use SilverStripe\GraphQL\Schema\Schema;

class PluginRegistry
{
    use Injectable;

    /**
     * @var array
     */
    private $plugins = [];

    /**
     * @param PluginInterface[] ...$plugins
     * @throws SchemaBuilderException
     */
    public function __construct(...$plugins)
    {
        foreach ($plugins as $plugin) {
            Schema::invariant(
                $plugin instanceof PluginInterface,
                '%s only accepts implementations of %s',
                __CLASS__,
                PluginInterface::class
            );
            $this->plugins[$plugin->getIdentifier()] = $plugin;
        }
    }

    /**
     * @param string $id
     * @return PluginInterface|null
     */
    public function getPluginByID(string $id): ?PluginInterface
    {
        return $this->plugins[$id] ?? null;
    }
}
