<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;

/**
 * Defines a class that transforms into a type or field creator
 * @package SilverStripe\GraphQL\Scaffolding\Scaffolders
 */
interface ScaffolderInterface
{
    /**
     * @param Manager $manager
     * @return mixed
     */
    public function getCreator(Manager $manager);
}