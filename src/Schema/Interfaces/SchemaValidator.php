<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;

interface SchemaValidator
{
    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void;
}
