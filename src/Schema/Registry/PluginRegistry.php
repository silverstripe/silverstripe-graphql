<?php


namespace SilverStripe\GraphQL\Schema\Registry;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\PluginInterface;
use SilverStripe\GraphQL\Schema\Schema;

/**
 * A central place for all the plugins to be registered and accessed by ID
 */
class PluginRegistry
{
    use Injectable;

    private array $plugins = [];

    /**
     * @param array ...$plugins
     * @throws SchemaBuilderException
     */
    public function __construct(...$plugins)
    {
        foreach ($plugins as $plugin) {
            $inst = Injector::inst()->get($plugin);
            Schema::invariant(
                $inst instanceof PluginInterface,
                '%s only accepts implementations of %s',
                __CLASS__,
                PluginInterface::class
            );
            $existing = $this->plugins[$inst->getIdentifier()] ?? null;
            Schema::invariant(
                !$existing || ($existing === $plugin),
                'Two different plugins are registered under identifier %s',
                $inst->getIdentifier()
            );
            $this->plugins[$inst->getIdentifier()] = $plugin;
        }
    }

    public function getPluginByID(string $id): ?PluginInterface
    {
        $class = $this->plugins[$id] ?? null;

        return $class ? Injector::inst()->create($class) : null;
    }
}
