<?php


namespace SilverStripe\GraphQL\Schema\Registry;


use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\PluginInterface;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * A central place for all the plugins to be registered and accessed by ID
 */
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
            $existing = $this->plugins[$plugin->getIdentifier()] ?? null;
            Schema::invariant(
                !$existing || (get_class($existing) === get_class($plugin)),
                'Two different plugins are registered under identifier %s',
                $plugin->getIdentifier()
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
