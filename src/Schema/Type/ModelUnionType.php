<?php


namespace SilverStripe\GraphQL\Schema\Type;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;

/**
 * Defines a union that is backed by a model definition
 */
class ModelUnionType extends UnionType
{
    /**
     * @var ModelInterfaceType
     */
    private $interface;

    /**
     * ModelUnionType constructor.
     * @param ModelInterfaceType $interface
     * @param string $name
     * @param array|null $config
     * @throws SchemaBuilderException
     */
    public function __construct(ModelInterfaceType $interface, string $name, ?array $config = null)
    {
        $this->setInterface($interface);
        parent::__construct($name, $config);
    }

    /**
     * @return ModelInterfaceType
     */
    public function getInterface(): ModelInterfaceType
    {
        return $this->interface;
    }

    /**
     * @param ModelInterfaceType $interface
     * @return $this
     */
    public function setInterface(ModelInterfaceType  $interface): self
    {
        $this->interface = $interface;

        return $this;
    }



}
