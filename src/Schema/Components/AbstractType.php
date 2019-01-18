<?php


namespace SilverStripe\GraphQL\Schema\Components;

abstract class AbstractType
{
    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @return array
     */
    abstract public function toArray();

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
