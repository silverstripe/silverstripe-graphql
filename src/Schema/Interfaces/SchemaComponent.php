<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

interface SchemaComponent
{
    /**
     * @return string|null
     */
    public function getName(): ?string;
}
