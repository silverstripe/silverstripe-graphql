<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\SchemaConfig;

/**
 * Implementors of this class can create a model for a given classname,
 * e.g. Blog -> DataObjectModel
 */
interface SchemaModelCreatorInterface
{
    /**
     * @param string $class
     * @return bool
     */
    public function appliesTo(string $class): bool;

    /**
     * @param string $class
     * @param SchemaConfig $context
     * @return SchemaModelInterface
     */
    public function createModel(string $class, SchemaConfig $context): SchemaModelInterface;
}
