<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;

/**
 * Implementors of this class that can validate that they are ready to be encoded into the schema
 */
interface SchemaValidator
{
    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void;
}
