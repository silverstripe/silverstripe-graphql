<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * Implementors of this interface can provide a description for a model type
 */
interface ModelTypeDescription
{
    /**
     * Return a description for the model type or null if no description is provided.
     */
    public function getTypeDescription(): ?string;
}
