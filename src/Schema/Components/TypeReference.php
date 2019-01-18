<?php


namespace SilverStripe\GraphQL\Schema\Components;

class TypeReference extends AbstractType
{
    /**
     * @var bool
     */
    protected $list = false;

    /**
     * @var bool
     */
    protected $required = false;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $type
     * @return TypeReference
     */
    public static function create($type)
    {
        return new static($type);
    }

    /**
     * TypeReference constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setList($bool)
    {
        $this->list = $bool;

        return $this;
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setRequired($bool)
    {
        $this->required = $bool;

        return $this;
    }

    /**
     * @return bool
     */
    public function isList()
    {
        return $this->list;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->getName(),
        ];
    }
}
