<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Manager;

/**
 * Defines a class that updates the Manager
 * @package SilverStripe\GraphQL\Scaffolding
 */
interface ManagerMutatorInterface
{
    /**
     * @param Manager $manager
     * @return mixed
     */
    public function addToManager(Manager $manager);
}