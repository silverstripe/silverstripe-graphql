<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


abstract class TypeAbstraction
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
     * @return string
     */
    abstract public function getName();

    /**
     * @return array
     */
    abstract public function toArray();

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
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}