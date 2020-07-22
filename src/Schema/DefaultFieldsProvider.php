<?php


namespace SilverStripe\GraphQL\Schema;


interface DefaultFieldsProvider
{
    /**
     * @return array
     */
    public function getDefaultFields(): array;

}
