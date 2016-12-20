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
     * @return mixed
     */
    public function addToManager(Manager $manager);
}