<?php

namespace SilverStripe\GraphQL\Schema\BulkLoader;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Interfaces\Identifiable;
use InvalidArgumentException;

/**
 * The instance of the registry, as composed by the Registry frontend
 */
class RegistryBackend
{
    use Injectable;

    /**
     * @var Identifiable[]
     */
    private array $instances = [];

    /**
     * RegistryBackend constructor.
     * @param Identifiable ...$instances
     */
    public function __construct(...$instances)
    {
        $this->setInstances($instances);
    }

    /**
     * @param Identifiable[] $instances
     * @return $this
     */
    public function setInstances(array $instances): self
    {
        foreach ($instances as $instance) {
            if (!is_subclass_of($instance, Identifiable::class)) {
                throw new InvalidArgumentException(sprintf(
                    'Class %s does not implement %s',
                    get_class($instance),
                    Identifiable::class
                ));
            }

            $this->instances[$instance->getIdentifier()] = $instance;
        }

        return $this;
    }

    /**
     * @param string $id
     * @return Identifiable|null
     */
    public function getByID(string $id): ?Identifiable
    {
        return $this->instances[$id] ?? null;
    }
}
