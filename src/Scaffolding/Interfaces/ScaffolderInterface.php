<?php

namespace SilverStripe\GraphQL\Scaffolding\Interfaces;

use SilverStripe\GraphQL\Manager;

/**
 * Defines a class that transforms into a type or field creator
 */
interface ScaffolderInterface
{
    /**
     * @param Manager $manager
     * @return mixed
     */
    public function scaffold(Manager $manager);
}
