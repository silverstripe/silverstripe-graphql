<?php


namespace SilverStripe\GraphQL\Schema\Components;


abstract class AbstractFunction
{
    /**
     * @return mixed
     */
    abstract public function export();
}