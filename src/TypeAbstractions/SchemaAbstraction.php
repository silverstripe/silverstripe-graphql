<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


use SilverStripe\GraphQL\Storage\Encode\TypeRegistryInterface;

class SchemaAbstraction
{
    /**
     * @var TypeRegistryInterface
     */
    protected $typeRegistry;

    /**
     * SchemaAbstraction constructor.
     * @param array $types
     */
    public function __construct(TypeRegistryInterface $registry)
    {
        $this->typeRegistry = $registry;
    }

    public function getQuery()
    {
        return $this->getTypeRegistry()->getType('Query');
    }

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
     * @return TypeRegistryInterface
     */
    public function getTypeRegistry()
    {
        return $this->typeRegistry;
    }

}