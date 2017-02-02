<?php

namespace SilverStripe\GraphQL\Scaffolding\Interfaces;

/**
 * Defines the methods required for a class to provide a CRUD scaffold
 */
interface CRUDInterface
{
    /**
     * @return string
     */
    public function getIdentifier();
}
