<?php


namespace SilverStripe\GraphQL\Schema;


interface OperationProvider
{
    /**
     * @param string $id
     * @return FieldAbstraction|null
     */
    public function getOperationCreatorByIdentifier(string $id): ?OperationCreator;
}
