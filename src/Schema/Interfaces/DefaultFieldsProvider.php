<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface DefaultFieldsProvider
{
    /**
     * @return array
     */
    public function getDefaultFields(): array;

}
