<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * Applies to an operation that was crated by a model
 */
interface ModelOperation
{
    public function getModel(): SchemaModelInterface;
}
