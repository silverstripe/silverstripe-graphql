<?php


namespace SilverStripe\GraphQL\Schema\Components;

class Enum extends AbstractType
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
     * @var string
     */
    protected $name;

    /**
     * Enum constructor.
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
     * @return Enum
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
     * @return Enum
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
