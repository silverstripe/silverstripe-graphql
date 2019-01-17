<?php

namespace SilverStripe\GraphQL\Storage\Encode;

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