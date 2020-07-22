<?php


namespace SilverStripe\GraphQL\Schema;


interface RequiredFieldsProvider
{
    /**
     * @return array
     */
    public function getRequiredFields(): array;

}
