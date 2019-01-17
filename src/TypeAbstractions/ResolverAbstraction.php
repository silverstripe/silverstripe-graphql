<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


abstract class ResolverAbstraction
{
    /**
     * @return callable
     */
    abstract public function export();
}