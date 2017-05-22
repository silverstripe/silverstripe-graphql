<?php

namespace SilverStripe\GraphQL\Scaffolding\Interfaces;

use SilverStripe\GraphQL\Manager;

/**
 * Defines a class that updates the Manager
 */
interface ManagerMutatorInterface
{
    /**
     * @param Manager $manager
     */
    public function addToManager(Manager $manager);
}
