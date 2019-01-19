<?php

namespace SilverStripe\GraphQL\Schema\Components;

use BadMethodCallException;

class LazyTypeReference extends TypeReference
{
    /**
     * @var callable
     */
    protected $callable;

    /**
     * @var AbstractType
     */
    protected $type;

    /**
     * LazyTypeReference constructor.
     * @param $callable
     */
    public function __construct($callable)
    {
        $this->callable = $callable;

        parent::__construct(null);
    }

    /**
     * @return string
     * @throws BadMethodCallException
     */
    public function getName()
    {
        if (!$this->type) {
            $this->execute();
        }

        return $this->type->getName();
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $this->type = call_user_func($this->callable);

        return $this;
    }
}
