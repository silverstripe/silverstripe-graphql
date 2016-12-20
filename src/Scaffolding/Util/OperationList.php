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
        foreach ($this->items as $key => $value) {
            if ($name === $value->getName()) {
                return $value;
            }
        }

        return false;
    }

    /**
     * @param  string $class
     * @return bool|OperationScaffold
     */
    public function findByType($class)
    {
    	foreach ($this->items as $key => $value) {
    		if($class === get_class($value)) {
    			return $value;
    		}
    	}

    	return false;
    }

    /**
     * @param $name
     */
    public function removeByName($name)
    {
        $renumberKeys = false;
        foreach ($this->items as $key => $value) {
            if ($name === $value->getName()) {
                $renumberKeys = true;
                unset($this->items[$key]);
            }
        }

        if ($renumberKeys) {
            $this->items = array_values($this->items);
        }
    }
}