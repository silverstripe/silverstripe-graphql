<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

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
     * @return SchemaModelInterface
     */
    public function createModel(string $class): SchemaModelInterface;
}
