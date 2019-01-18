<?php


namespace SilverStripe\GraphQL\Schema\Components;

class Argument
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
     * @var TypeReference
     */
    protected $type;

    /**
     * @var null
     */
    protected $defaultValue = null;

    /**
     * Field constructor.
     * @param string $name
     * @param TypeReference $type
     */
    public function __construct($name, TypeReference $type)
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
     * @return $this
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
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return TypeReference
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param TypeReference $type
     * @return $this
     */
    public function setType(TypeReference $type)
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
     * @return Argument
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
