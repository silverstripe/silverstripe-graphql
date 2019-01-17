<?php

namespace SilverStripe\GraphQL\Schema\Storage\Encoding\Interfaces;

use Closure;

interface ClosureFactoryInterface
{
    /**
     * @return Closure
     */
    public function createClosure();

    /**
     * @return array
     */
    public function getContext();
}