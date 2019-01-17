<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


abstract class TypeAbstraction
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