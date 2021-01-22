<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\SchemaContext;

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
     * @param SchemaContext $context
     * @return SchemaModelInterface
     */
    public function createModel(string $class, SchemaContext $context): SchemaModelInterface;
}
