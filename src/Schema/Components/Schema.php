<?php


namespace SilverStripe\GraphQL\Schema\Components;

use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeRegistryInterface;

class Schema
{
    /**
     * @var TypeRegistryInterface
     */
    protected $typeRegistry;

    /**
     * Schema constructor.
     * @param TypeRegistryInterface $registry
     */
    public function __construct(TypeRegistryInterface $registry)
    {
        $this->typeRegistry = $registry;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->getTypeRegistry()->getType('Query');
    }

    /**
     * @return mixed
     */
    public function getMutation()
    {
        return $this->getTypeRegistry()->getType('Mutation');
    }

    /**
     * @param TypeRegistryInterface $registry
     * @return $this
     */
    public function setTypeRegistry(TypeRegistryInterface $registry)
    {
        $this->typeRegistry = $registry;

        return $this;
    }

    /**
     * @return  TypeRegistryInterface
     */
    public function getTypeRegistry()
    {
        return $this->typeRegistry;
    }
}
