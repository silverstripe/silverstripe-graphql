<?php

namespace SilverStripe\GraphQL\Scaffolding\Util;

use SilverStripe\GraphQL\Scaffolding\Scaffolders\OperationScaffolder;
use SilverStripe\ORM\ArrayList;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

/**
 * An array list designed to work with OperationScaffolders
 */
class OperationList extends ArrayList
{
    /**
     * OperationList constructor.
     * @param array $items
     */
    public function __construct($items = [])
    {
        foreach ($items as $item) {
            if (!$item instanceof OperationScaffold) {
                throw new InvalidArgumentException(
                    '%s only accepts instances of %s',
                    __CLASS__,
                    OperationScaffold::class
                );
            }
        }

        parent::__construct($items);
    }

    /**
     * @param array|object $item
     */
    public function push($item)
    {
        if (!$item instanceof OperationScaffolder) {
            throw new InvalidArgumentException(sprintf(
                '%s only accepts instances of %s',
                __CLASS__,
                OperationScaffolder::class
            ));
        }

        parent::push($item);
    }

    /**
     * @param $name
     * @return bool|OperationScaffold
     */
    public function findByName($name)
    {
        return $this->findItemByCallback(function ($item) use ($name) {
            return $name === $item->getName();
        });
    }

    /**
     * @param  string $id
     * @return bool|OperationScaffold
     */
    public function findByIdentifier($id)
    {
        return $this->findItemByCallback(function ($item) use ($id) {
            return $id === $item->getIdentifier();
        });
    }

    /**
     * @param $name
     */
    public function removeByName($name)
    {
        $this->removeItemByCallback(function ($operation) use ($name) {
            return $operation->getName() === $name;
        });
    }

    /**
     * @param $name
     */
    public function removeByIdentifier($id)
    {
        $this->removeItemByCallback(function ($operation) use ($id) {
            return $operation->getIdentifier() === $id;
        });
    }

    /**
     * @param \Closure
     */
    public function removeItemByCallback($callback)
    {
        $renumberKeys = false;
        foreach ($this->items as $key => $value) {
            if ($callback($value)) {
                $renumberKeys = true;
                unset($this->items[$key]);
            }
        }

        if ($renumberKeys) {
            $this->items = array_values($this->items);
        }
    }

    /**
     * @param \Closure
     */
    public function findItemByCallback($callback)
    {
        foreach ($this->items as $key => $value) {
            if ($callback($value)) {
                return $value;
            }
        }

        return false;

    }
}
