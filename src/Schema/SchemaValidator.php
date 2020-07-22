<?php


namespace SilverStripe\GraphQL\Schema;


interface SchemaValidator
{
    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void;
}
