<?php

namespace SilverStripe\GraphQL\Scaffolding\Traits;

trait CRUDTrait
{
    /**
     * CRUD constructor.
     *
     * @param string $dataObjectClass
     */
    public function __construct($dataObjectClass)
    {
        parent::__construct(null, null, $this, $dataObjectClass);
    }

}