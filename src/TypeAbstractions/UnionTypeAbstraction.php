<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


class UnionTypeAbstraction extends TypeAbstraction
{
    /**
     * @var string
     */
    protected $description;

    /**
     * @var RegistryResolverAbstraction
     */
    protected $typeFactory;

    /**
     * @var RegistryResolverAbstraction
     */
    protected $resolveTypeFactory;

    /**
     * UnionTypeAbstraction constructor.
     * @param $name
     * @param RegistryResolverAbstraction $typeFactory
     * @param RegistryResolverAbstraction $resolveTypeFactory
     */
    public function __construct(
        $name,
        RegistryResolverAbstraction $typeFactory,
        RegistryResolverAbstraction $resolveTypeFactory
    ) {
        $this->name = $name;
        $this->typeFactory = $typeFactory;
        $this->resolveTypeFactory = $resolveTypeFactory;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return UnionTypeAbstraction
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return RegistryResolverAbstraction
     */
    public function getTypeFactory()
    {
        return $this->typeFactory;
    }

    /**
     * @param RegistryResolverAbstraction $typeFactory
     * @return UnionTypeAbstraction
     */
    public function setTypeFactory(RegistryResolverAbstraction $typeFactory)
    {
        $this->typeFactory = $typeFactory;

        return $this;
    }

    /**
     * @return RegistryResolverAbstraction
     */
    public function getResolveTypeFactory()
    {
        return $this->resolveTypeFactory;
    }

    /**
     * @param RegistryResolverAbstraction $resolveTypeFactory
     * @return UnionTypeAbstraction
     */
    public function setResolveTypeFactory(RegistryResolverAbstraction $resolveTypeFactory)
    {
        $this->resolveTypeFactory = $resolveTypeFactory;

        return $this;
    }
    
    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'resolveType' => $this->getResolveTypeFactory(),
            'types' => $this->getTypeFactory(),
        ];
    }
}