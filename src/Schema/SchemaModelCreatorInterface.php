<?php


namespace SilverStripe\GraphQL\Schema;

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
