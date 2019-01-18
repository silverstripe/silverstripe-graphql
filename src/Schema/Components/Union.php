<?php


namespace SilverStripe\GraphQL\Schema\Components;

class Union extends AbstractType
{
    /**
     * @var string
     */
    protected $description;

    /**
     * @var RegistryFunction
     */
    protected $typeFactory;

    /**
     * @var RegistryFunction
     */
    protected $resolveTypeFactory;

    /**
     * @var string
     */
    protected $name;

    /**
     * Union constructor.
     * @param $name
     * @param RegistryFunction $typeFactory
     * @param RegistryFunction $resolveTypeFactory
     */
    public function __construct(
        $name,
        RegistryFunction $typeFactory,
        RegistryFunction $resolveTypeFactory
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
     * @return Union
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return RegistryFunction
     */
    public function getTypeFactory()
    {
        return $this->typeFactory;
    }

    /**
     * @param RegistryFunction $typeFactory
     * @return Union
     */
    public function setTypeFactory(RegistryFunction $typeFactory)
    {
        $this->typeFactory = $typeFactory;

        return $this;
    }

    /**
     * @return RegistryFunction
     */
    public function getResolveTypeFactory()
    {
        return $this->resolveTypeFactory;
    }

    /**
     * @param RegistryFunction $resolveTypeFactory
     * @return Union
     */
    public function setResolveTypeFactory(RegistryFunction $resolveTypeFactory)
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