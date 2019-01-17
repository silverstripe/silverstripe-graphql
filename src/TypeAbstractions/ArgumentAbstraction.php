<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


use SilverStripe\GraphQL\Scaffolding\Interfaces\ResolverFactory;
use SilverStripe\GraphQL\Storage\Encode\ClosureFactoryInterface;

class ArgumentAbstraction
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var ReferentialTypeAbstraction
     */
    protected $type;

    /**
     * @var null 
     */
    protected $defaultValue = null;

    /**
     * FieldAbstraction constructor.
     * @param string $name
     * @param ReferentialTypeAbstraction $type
     */
    public function __construct($name, ReferentialTypeAbstraction $type)
    {
        $this->setName($name)
            ->setType($type);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return FieldAbstraction
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
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
     * @return FieldAbstraction
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return ReferentialTypeAbstraction
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param ReferentialTypeAbstraction $type
     * @return $this
     */
    public function setType(ReferentialTypeAbstraction $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     * @return ArgumentAbstraction
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'defaultValue' => $this->getDefaultValue(),
        ];
    }
    
}