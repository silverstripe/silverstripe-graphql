<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


class EnumAbstraction extends TypeAbstraction
{
    /**
     * @var string
     */
    protected $description = null;

    /**
     * @var array 
     */
    protected $values = [];

    /**
     * EnumAbstraction constructor.
     * @param $name
     * @param null $description
     * @param array $values
     */
    public function __construct($name, $description = null, $values = [])
    {
        $this->setName($name)
            ->setDescription($description)
            ->setValues($values);
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
     * @return EnumAbstraction
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $values
     * @return EnumAbstraction
     */
    public function setValues($values)
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'values' => $this->getValues(),
        ];
    }
}