<?php

namespace SilverStripe\GraphQL;

use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\Interfaces\TypeStoreInterface;
use BadMethodCallException;
use SilverStripe\GraphQL\Serialisation\TypeStoreConsumer;

class TypeStore implements TypeStoreInterface
{
    /**
     * @var array
     */
    protected $registry = [];

    /**
     * @var bool
     */
    protected $frozen = false;

    /**
     * @param string $name
     * @return Type|null
     */
    public function getType($name)
    {
        if ($this->hasType($name)) {
            $type = $this->registry[$name];
            if (is_callable($type)) {
                $this->registry[$name] = $type();
            }

            return $this->registry[$name];
        }

        return null;
    }

    /**
     * @param Type $type
     * @param string|null $name
     */
    public function addType(Type $type, $name = null)
    {
        if ($this->frozen) {
            throw new BadMethodCallException(sprintf(
                'Attempted to add type %s after type store was frozen',
                $type->name
            ));
        }
        $typeName = $name ?: (string) $type;
        if (!$this->hasType($typeName)) {
            $this->registry[$typeName] = function () use ($type) {
                if ($type instanceof TypeStoreConsumer) {
                    $type->loadFromTypeStore($this);
                }
                return $type;
            };
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasType($name)
    {
        return isset($this->registry[$name]);
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->registry;
    }

    /**
     * @param array $types
     * @return $this|mixed
     */
    public function initialise($types)
    {
        foreach ($types as $name => $type) {
            $this->addType($type, $name);
        }

        return $this;
    }

    public function __sleep()
    {
        $data = [];
        foreach ($this->registry as $name => $type) {
            $data[$name] = is_callable($type) ? $type() : $type;
        }

        $this->registry = $data;

        return [
            'registry',
        ];
    }

}