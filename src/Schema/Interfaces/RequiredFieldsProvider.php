<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface RequiredFieldsProvider
{
    /**
     * @return array
     */
    public function getRequiredFields(): array;

}
