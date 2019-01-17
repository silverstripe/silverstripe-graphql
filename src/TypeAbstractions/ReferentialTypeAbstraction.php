<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


class ReferentialTypeAbstraction extends TypeAbstraction
{
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

    public function toArray()
    {
        return [
            'name' => $this->name
        ];
    }
}